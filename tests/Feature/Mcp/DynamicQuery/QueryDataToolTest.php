<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\QueryDataTool;
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

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'MCP Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => MsProjectRole::query()->firstOrFail()->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);

    Task::create([
        'project_id' => $this->project->id,
        'title' => 'Findable Task',
        'progress' => 0,
        'sequence_number' => 1,
        'status_id' => MsTaskStatus::query()->firstOrFail()->id,
        'type_id' => MsTaskType::query()->firstOrFail()->id,
        'task_category_id' => TaskCategory::query()->firstOrFail()->id,
    ]);
});

it('returns query results for a member', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryDataTool::class, [
            'model' => 'task',
            'select' => ['id', 'title'],
            'filters' => [
                ['column' => 'project_id', 'operator' => '=', 'value' => Sqids::encode($this->project->id)],
            ],
        ]);

    $response->assertOk();
    $response->assertSee('Findable Task');
});

it('returns an actionable error for an unknown model', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryDataTool::class, ['model' => 'secrets']);

    $response->assertHasErrors();
});

it('returns an actionable error when combining with and aggregates', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryDataTool::class, [
            'model' => 'task',
            'with' => ['status'],
            'aggregates' => [['function' => 'count', 'column' => 'tasks.id', 'alias' => 'total']],
        ]);

    $response->assertHasErrors();
});
