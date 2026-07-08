<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\UpdateTaskStatusTool;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded owned_id/created_by FKs resolve
    // under Postgres' non-transactional sequences (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
    ]);

    $this->todo = MsTaskStatus::where('name', 'To Do')->firstOrFail();
    $this->inProgress = MsTaskStatus::where('name', 'In Progress')->firstOrFail();
    $this->completed = MsTaskStatus::where('name', 'Completed')->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'MCP Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    // The acting user is the project Owner, satisfying the task `update` policy
    // (task.update permission + is_owner) used by TaskController::updateStatus.
    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => MsProjectRole::where('name', 'Owner')->firstOrFail()->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);

    $this->user->givePermissionTo(Permission::findOrCreate('task.update', 'web'));

    $this->task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'My Task',
        'progress' => 0,
        'sequence_number' => 1,
        'status_id' => $this->todo->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
        'start_date' => '2026-01-01',
    ]);

    $this->encodedTask = Sqids::encode($this->task->id);
});

it('updates the task status to Completed and recalculates progress', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateTaskStatusTool::class, [
            'task' => $this->encodedTask,
            'status_id' => Sqids::encode($this->completed->id),
            'due_date' => '2026-02-01',
        ]);

    $response->assertOk();

    $fresh = $this->task->fresh();
    expect($fresh->status_id)->toBe($this->completed->id)
        ->and($fresh->completed_at)->not->toBeNull()
        // a leaf task set to Completed reaches 100% progress
        ->and((int) $fresh->progress)->toBe(100)
        // due_date is a plain string at runtime (the Task model does not cast it)
        ->and((string) $fresh->due_date)->toStartWith('2026-02-01');
});

it('updates to a due-date-exempt status (To Do) without a due date', function () {
    $this->task->update(['status_id' => $this->inProgress->id]);

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateTaskStatusTool::class, [
            'task' => $this->encodedTask,
            'status_id' => Sqids::encode($this->todo->id),
        ]);

    $response->assertOk();
    expect($this->task->fresh()->status_id)->toBe($this->todo->id);
});

it('requires a due date for a status that demands one when the task has none', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateTaskStatusTool::class, [
            'task' => $this->encodedTask,
            'status_id' => Sqids::encode($this->inProgress->id),
        ]);

    $response->assertHasErrors();
    expect($this->task->fresh()->status_id)->toBe($this->todo->id);
});

it('errors and changes nothing when the user cannot update the task', function () {
    $outsider = User::factory()->create(['id' => 2]);

    $response = ProjectManagementServer::actingAs($outsider)
        ->tool(UpdateTaskStatusTool::class, [
            'task' => $this->encodedTask,
            'status_id' => Sqids::encode($this->todo->id),
        ]);

    $response->assertHasErrors();
    expect($this->task->fresh()->status_id)->toBe($this->todo->id);
});

it('errors when the task id is invalid', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateTaskStatusTool::class, [
            'task' => 'not-a-valid-id',
            'status_id' => Sqids::encode($this->todo->id),
        ]);

    $response->assertHasErrors();
});

it('errors when the status_id is not a valid option', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateTaskStatusTool::class, [
            'task' => $this->encodedTask,
            'status_id' => Sqids::encode(999999),
        ]);

    $response->assertHasErrors();
    expect($this->task->fresh()->status_id)->toBe($this->todo->id);
});
