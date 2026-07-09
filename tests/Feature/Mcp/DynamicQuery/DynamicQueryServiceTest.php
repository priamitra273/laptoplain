<?php

use App\Facades\Sqids;
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
use App\Services\DynamicQuery\DynamicQueryService;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;

beforeEach(function () {
    // Pin user id 1 so the seeders' hardcoded FKs resolve (see TaskProjectToolsTest).
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

    $this->makeProject = function (User $owner, string $title): Project {
        $project = Project::create([
            'status_id' => MsProjectStatus::query()->firstOrFail()->id,
            'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
            'owner_id' => $owner->id,
            'owned_id' => $owner->id,
            'title' => $title,
            'emoji' => '🚀',
            'progress' => 0,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'project_role_id' => MsProjectRole::query()->firstOrFail()->id,
            'owned_id' => $owner->id,
            'is_active' => true,
        ]);

        return $project;
    };

    $this->service = app(DynamicQueryService::class);
});

it('returns base rows for a member with encoded ids', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Alpha',
        'progress' => 0,
        'sequence_number' => 1,
        'status_id' => $this->status->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
    ]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['id', 'title', 'project_id'],
    ], $this->user);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['title'])->toBe('Alpha')
        ->and($rows[0]['id'])->toBe(Sqids::encode($task->id))          // encoded, not raw int
        ->and($rows[0]['project_id'])->toBe(Sqids::encode($project->id));
});

it('scopes rows to the visible projects of the acting user', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    $theirs = ($this->makeProject)($outsider, 'Theirs');

    Task::create(['project_id' => $mine->id, 'title' => 'Mine task', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $theirs->id, 'title' => 'Their task', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run(['model' => 'task', 'select' => ['title']], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Mine task']);
});

it('filters with a decoded sqid id and honors the limit', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'Keep', 'progress' => 80, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'Drop', 'progress' => 10, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['title', 'progress'],
        'filters' => [
            ['column' => 'project_id', 'operator' => '=', 'value' => Sqids::encode($project->id)],
            ['column' => 'progress', 'operator' => '>=', 'value' => 50],
        ],
        'order_by' => [['column' => 'progress', 'direction' => 'desc']],
    ], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Keep']);
});

it('does not let an or-boolean filter bypass project scoping', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    $theirs = ($this->makeProject)($outsider, 'Theirs');

    Task::create(['project_id' => $mine->id, 'title' => 'Mine task', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $theirs->id, 'title' => 'Their task', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['title'],
        'filters' => [
            ['column' => 'progress', 'operator' => '>=', 'value' => 0, 'boolean' => 'or'],
        ],
    ], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Mine task']);
});

it('loads whitelisted relations as nested data', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'With status', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['id', 'title', 'status_id'],
        'with' => ['status'],
    ], $this->user);

    expect($rows[0]['status'])->toBeArray()
        ->and($rows[0]['status']['name'])->toBe($this->status->name)
        ->and($rows[0]['status']['id'])->toBe(Sqids::encode($this->status->id));
});

it('scopes a project-scoped relation loaded from a global base model', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    ($this->makeProject)($outsider, 'Theirs');

    $rows = $this->service->run([
        'model' => 'user',
        'select' => ['id', 'name'],
        'with' => ['projects'],
        'filters' => [['column' => 'id', 'operator' => '=', 'value' => Sqids::encode($this->user->id)]],
    ], $this->user);

    // The acting user only sees their own project through the relation.
    expect(collect($rows[0]['projects'])->pluck('title')->all())->toBe(['Mine']);
});
