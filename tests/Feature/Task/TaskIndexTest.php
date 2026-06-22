<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;

beforeEach(function () {
    // Master seeders hardcode owned_id/created_by = 1 with an FK to users.
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
    ]);

    // "super-admin-admin" bypasses the route.permission middleware (prefix "super-admin-")
    // and makes every project visible via Project::scopeVisibleFor().
    Role::create([
        'name' => 'super-admin-admin',
        'guard_name' => 'web',
        'label' => 'Super Admin',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-admin');

    $this->status = MsTaskStatus::query()->orderBy('id')->firstOrFail();
    $this->otherStatus = MsTaskStatus::query()->orderBy('id')->skip(1)->firstOrFail();
    $this->priority = MsTaskPriority::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'My Project',
        'emoji' => '📦',
        'progress' => 0,
    ]);
});

function makeMyTask(int $projectId, array $attrs = []): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ], $attrs));
}

function assignTask(Task $task, int $userId): Task
{
    $task->users()->attach($userId);

    return $task;
}

it('board view returns columns of slim tasks with subtask counts and no recursive tree', function () {
    $root = makeMyTask($this->project->id, [
        'title' => 'Root',
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
    ]);
    makeMyTask($this->project->id, ['title' => 'Child done', 'parent_id' => $root->id, 'completed_at' => now()]);
    makeMyTask($this->project->id, ['title' => 'Child open', 'parent_id' => $root->id]);
    assignTask($root, $this->user->id);

    $response = $this->actingAs($this->user)->get(route('task.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project/task/Index')
        ->where('view', 'board')
        ->has('board') // one column per status
        ->whereType('board.0.status.id', 'string')
        ->where('board.0.total', 1)
        ->where('board.0.has_more', false)
        ->has('board.0.tasks', 1)
        ->whereType('board.0.tasks.0.id', 'string')
        ->where('board.0.tasks.0.sub_task_count', 2)
        ->where('board.0.tasks.0.sub_task_done_count', 1)
        ->missing('board.0.tasks.0.sub_task_recursive')
        ->where('board.0.tasks.0.project.title', 'My Project')
    );
});

it('caps each board column at 10 tasks and flags has_more with the true total', function () {
    foreach (range(1, 12) as $i) {
        assignTask(makeMyTask($this->project->id, ['title' => "Task {$i}", 'sequence_number' => $i, 'status_id' => $this->status->id]), $this->user->id);
    }

    $response = $this->actingAs($this->user)->get(route('task.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('board.0.total', 12)
        ->where('board.0.has_more', true)
        ->has('board.0.tasks', 10)
    );
});

it('paginates a single board column via the load-more endpoint', function () {
    foreach (range(1, 12) as $i) {
        assignTask(makeMyTask($this->project->id, ['title' => "Task {$i}", 'sequence_number' => $i, 'status_id' => $this->status->id]), $this->user->id);
    }

    $page1 = $this->actingAs($this->user)->getJson(route('task.board', [
        'status_id' => Sqids::encode($this->status->id),
        'page' => 1,
        'per_page' => 10,
    ]));
    $page1->assertSuccessful()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('has_more', true)
        ->assertJsonPath('next_page', 2);

    $page2 = $this->actingAs($this->user)->getJson(route('task.board', [
        'status_id' => Sqids::encode($this->status->id),
        'page' => 2,
        'per_page' => 10,
    ]));
    $page2->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('next_page', null);
});

it('list view returns a server-side paginator', function () {
    foreach (range(1, 3) as $i) {
        assignTask(makeMyTask($this->project->id, ['title' => "Task {$i}", 'sequence_number' => $i, 'status_id' => $this->status->id]), $this->user->id);
    }

    $response = $this->actingAs($this->user)->get(route('task.index', ['view' => 'list', 'per_page' => 2]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('view', 'list')
        ->has('tasks.data', 2)
        ->where('tasks.total', 3)
        ->where('tasks.per_page', 2)
        ->where('tasks.current_page', 1)
        ->whereType('tasks.data.0.id', 'string')
    );
});

it('applies a server-side title search filter', function () {
    assignTask(makeMyTask($this->project->id, ['title' => 'Alpha task', 'status_id' => $this->status->id]), $this->user->id);
    assignTask(makeMyTask($this->project->id, ['title' => 'Beta task', 'sequence_number' => 2, 'status_id' => $this->status->id]), $this->user->id);

    $response = $this->actingAs($this->user)->get(route('task.index', ['view' => 'list', 'search' => 'Alpha']));

    $response->assertInertia(fn ($page) => $page
        ->has('tasks.data', 1)
        ->where('tasks.data.0.title', 'Alpha task')
    );
});

it('applies a server-side project filter', function () {
    $otherProject = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Other Project',
        'emoji' => '📁',
        'progress' => 0,
    ]);
    assignTask(makeMyTask($this->project->id, ['title' => 'Mine', 'status_id' => $this->status->id]), $this->user->id);
    assignTask(makeMyTask($otherProject->id, ['title' => 'Other', 'status_id' => $this->status->id]), $this->user->id);

    $response = $this->actingAs($this->user)->get(route('task.index', [
        'view' => 'list',
        'project_id' => Sqids::encode($this->project->id),
    ]));

    $response->assertInertia(fn ($page) => $page
        ->has('tasks.data', 1)
        ->where('tasks.data.0.title', 'Mine')
    );
});

it('returns per-status summary counts', function () {
    assignTask(makeMyTask($this->project->id, ['title' => 'A', 'status_id' => $this->status->id]), $this->user->id);
    assignTask(makeMyTask($this->project->id, ['title' => 'B', 'sequence_number' => 2, 'status_id' => $this->status->id]), $this->user->id);
    assignTask(makeMyTask($this->project->id, ['title' => 'C', 'sequence_number' => 3, 'status_id' => $this->otherStatus->id]), $this->user->id);

    $response = $this->actingAs($this->user)->get(route('task.index'));

    $response->assertInertia(function ($page) {
        $summary = collect($page->toArray()['props']['summary']);
        expect($summary->firstWhere('id', Sqids::encode($this->status->id))['count'])->toBe(2)
            ->and($summary->firstWhere('id', Sqids::encode($this->otherStatus->id))['count'])->toBe(1);
    });
});

it('does not send the unused categories prop and ships option lists', function () {
    assignTask(makeMyTask($this->project->id, ['title' => 'Solo', 'status_id' => $this->status->id]), $this->user->id);

    $response = $this->actingAs($this->user)->get(route('task.index'));

    $response->assertInertia(fn ($page) => $page
        ->missing('categories')
        ->has('statuses')
        ->has('priorities')
        ->has('types')
        ->has('projects')
        ->whereType('projects.0.id', 'string')
    );
});
