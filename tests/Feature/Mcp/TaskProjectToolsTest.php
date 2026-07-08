<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\TaskProjectTools;
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

    $this->status = MsTaskStatus::query()->firstOrFail();
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

    // The acting user has access by being a member of the project (Project::visibleFor).
    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => MsProjectRole::query()->firstOrFail()->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

it('returns the recursive task tree for a project member, with sqid-encoded ids', function () {
    $root = Task::create([
        'project_id' => $this->project->id,
        'title' => 'Root Task',
        'progress' => 0,
        'sequence_number' => 1,
        'status_id' => $this->status->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
    ]);

    $child = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $root->id,
        'title' => 'Child Task',
        'progress' => 0,
        'sequence_number' => 2,
    ]);

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(TaskProjectTools::class, ['project' => $this->encoded]);

    $response->assertOk();

    // Recursive shape: child is nested under the root via sub_task_recursive.
    $response->assertSee('sub_task_recursive');
    $response->assertSee('Root Task');
    $response->assertSee('Child Task');

    // Ids are sqid strings (encoded by rec_encode_ids_in_list), not raw integers.
    $response->assertSee(Sqids::encode($root->id));
    $response->assertSee(Sqids::encode($child->id));
});

it('errors when the acting user is not a member of the project', function () {
    $outsider = User::factory()->create(['id' => 2]);

    $response = ProjectManagementServer::actingAs($outsider)
        ->tool(TaskProjectTools::class, ['project' => $this->encoded]);

    $response->assertHasErrors();
});

it('errors when the project id is invalid', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(TaskProjectTools::class, ['project' => 'not-a-valid-id']);

    $response->assertHasErrors();
});
