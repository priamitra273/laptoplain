<?php

use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Repositories\ProjectRepository;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsSprintStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Master seeders hardcode owned_id/created_by = 1 with an FK to users.
    // Postgres sequences are non-transactional, so RefreshDatabase does not
    // reset them between tests; pin the user to id 1 so seeding always resolves.
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
        MsSprintStatusSeeder::class,
    ]);

    $this->status = MsTaskStatus::query()->firstOrFail();
    $this->priority = MsTaskPriority::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->project = makeTreeProject($this->user);
});

function makeTreeProject(User $user): Project
{
    return Project::create([
        'status_id' => \App\Models\MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => \App\Models\MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $user->id,
        'owned_id' => $user->id,
        'title' => 'Tree Project',
        'emoji' => '🌳',
        'progress' => 0,
    ]);
}

function makeTreeTask(int $projectId, array $attrs): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ], $attrs));
}

it('loads a recursive tree, excludes soft-deleted tasks, and nests correctly', function () {
    $root = makeTreeTask($this->project->id, [
        'title' => 'Root', 'status_id' => $this->status->id, 'priority_id' => $this->priority->id,
        'type_id' => $this->type->id, 'task_category_id' => $this->category->id, 'sequence_number' => 1,
    ]);
    $child = makeTreeTask($this->project->id, ['title' => 'Child', 'parent_id' => $root->id, 'sequence_number' => 1]);
    $grandchild = makeTreeTask($this->project->id, ['title' => 'Grandchild', 'parent_id' => $child->id, 'sequence_number' => 1]);
    $deleted = makeTreeTask($this->project->id, ['title' => 'Deleted', 'parent_id' => $root->id, 'sequence_number' => 2]);

    $root->users()->attach($this->user->id);
    $deleted->delete();

    $tree = app(ProjectRepository::class)->getTaskTree($this->project->id)->toCollection();

    expect($tree)->toHaveCount(1);
    $rootData = $tree->first();
    expect($rootData->id)->toBe($root->id)
        ->and($rootData->sub_task_recursive)->toHaveCount(1) // 'Deleted' excluded
        ->and($rootData->users)->toHaveCount(1);

    $childData = $rootData->sub_task_recursive->toCollection()->first();
    expect($childData->id)->toBe($child->id)
        ->and($childData->sub_task_recursive->toCollection()->first()->id)->toBe($grandchild->id);
});

it('keeps relation query count constant regardless of tree depth', function () {
    $shallow = makeTreeProject($this->user);
    makeTreeTask($shallow->id, ['title' => 'A', 'sequence_number' => 1]);
    makeTreeTask($shallow->id, ['title' => 'B', 'sequence_number' => 2]);

    $deep = makeTreeProject($this->user);
    $parentId = null;
    for ($i = 0; $i < 5; $i++) {
        $parentId = makeTreeTask($deep->id, ['title' => "L{$i}", 'parent_id' => $parentId, 'sequence_number' => 1])->id;
    }

    $repo = app(ProjectRepository::class);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $repo->getTaskTree($shallow->id);
    $shallowCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $repo->getTaskTree($deep->id);
    $deepCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($deepCount)->toBe($shallowCount);
});

it('renders project/Detail with ProjectTaskData-shaped tasks', function () {
    // Super-admin role grants both the route.permission middleware bypass and a
    // full-access policy (ProjectService::getAuthUserPolicy). The roles table has
    // non-nullable label/team_id/is_active columns (team_id has no FK in test db).
    \App\Models\Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $root = makeTreeTask($this->project->id, [
        'title' => 'Root', 'status_id' => $this->status->id, 'priority_id' => $this->priority->id,
        'type_id' => $this->type->id, 'task_category_id' => $this->category->id, 'sequence_number' => 1, 'due_date' => '2026-01-01',
    ]);
    makeTreeTask($this->project->id, ['title' => 'Child', 'parent_id' => $root->id, 'sequence_number' => 1]);
    $root->users()->attach($this->user->id);

    $encoded = \App\Facades\Sqids::encode($this->project->id);

    $response = $this->actingAs($this->user)->get("/project/{$encoded}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project/Detail')
        ->has('tasks', 1)
        ->has('tasks.0.sub_task_recursive', 1)
        ->has('tasks.0.status.score')
        ->has('tasks.0.users.0.avatar_url')
        ->where('tasks.0.is_overdue', true)
        ->missing('tasks.0.users.0.pivot')
    );
});
