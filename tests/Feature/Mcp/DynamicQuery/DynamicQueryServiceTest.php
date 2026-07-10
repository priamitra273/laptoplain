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

it('scopes a project-scoped relation to the acting user, not the row owner', function () {
    ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    ($this->makeProject)($outsider, 'Theirs');

    // Acting as $this->user (who can see only 'Mine'), load the OUTSIDER's user
    // row with its projects. The outsider is a member of 'Theirs', but the acting
    // user cannot see 'Theirs', so the nested projects must be empty — scope
    // follows the actor, not the row owner.
    $rows = $this->service->run([
        'model' => 'user',
        'select' => ['id', 'name'],
        'with' => ['projects'],
        'filters' => [['column' => 'id', 'operator' => '=', 'value' => Sqids::encode($outsider->id)]],
    ], $this->user);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['projects'])->toBe([]);
});

it('runs an explicit join and returns flat rows', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'Joined', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['tasks.title', 'ms_task_statuses.name'],
        'joins' => [[
            'type' => 'left',
            'model' => 'ms_task_status',
            'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
        ]],
    ], $this->user);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['title'])->toBe('Joined')
        ->and($rows[0]['name'])->toBe($this->status->name);
});

it('scopes join-mode queries to visible projects', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    $theirs = ($this->makeProject)($outsider, 'Theirs');

    Task::create(['project_id' => $mine->id, 'title' => 'Mine', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $theirs->id, 'title' => 'Theirs', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['tasks.title'],
        'joins' => [[
            'model' => 'ms_task_status',
            'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
        ]],
    ], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Mine']);
});

it('aggregates task counts grouped by a joined column', function () {
    $project = ($this->makeProject)($this->user, 'Mine');
    $other = MsTaskStatus::query()->where('id', '!=', $this->status->id)->firstOrFail();

    Task::create(['project_id' => $project->id, 'title' => 'A', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'B', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'C', 'progress' => 0, 'sequence_number' => 3, 'status_id' => $other->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['ms_task_statuses.name'],
        'joins' => [[
            'model' => 'ms_task_status',
            'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
        ]],
        'group_by' => ['ms_task_statuses.name'],
        'aggregates' => [['function' => 'count', 'column' => 'tasks.id', 'alias' => 'total']],
        'order_by' => [['column' => 'total', 'direction' => 'desc']],
    ], $this->user);

    expect($rows[0]['name'])->toBe($this->status->name)
        ->and((int) $rows[0]['total'])->toBe(2);
});

it('counts all visible tasks as a global aggregate with no select or group_by', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'A', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'B', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'aggregates' => [['function' => 'count', 'column' => '*', 'alias' => 'total']],
    ], $this->user);

    expect($rows)->toHaveCount(1)
        ->and((int) $rows[0]['total'])->toBe(2);
});

it('groups an aggregate by a base column with no explicit select', function () {
    $project = ($this->makeProject)($this->user, 'Mine');
    $other = MsTaskStatus::query()->where('id', '!=', $this->status->id)->firstOrFail();

    Task::create(['project_id' => $project->id, 'title' => 'A', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'B', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'C', 'progress' => 0, 'sequence_number' => 3, 'status_id' => $other->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'group_by' => ['tasks.status_id'],
        'aggregates' => [['function' => 'count', 'column' => '*', 'alias' => 'total']],
        'order_by' => [['column' => 'total', 'direction' => 'desc']],
    ], $this->user);

    expect($rows)->toHaveCount(2)
        ->and((int) $rows[0]['total'])->toBe(2)
        ->and((int) $rows[1]['total'])->toBe(1);
});

it('never selects sensitive user columns even with default select', function () {
    ($this->makeProject)($this->user, 'Mine');

    $rows = $this->service->run(['model' => 'user'], $this->user);

    expect($rows[0])->not->toHaveKey('password')
        ->and($rows[0])->not->toHaveKey('remember_token')
        ->and($rows[0])->not->toHaveKey('uuid')   // uuid is NOT in User::$hidden — real allowlist check
        ->and($rows[0])->toHaveKey('email');
});

it('never selects sensitive user columns in join mode (no eloquent hidden backstop)', function () {
    ($this->makeProject)($this->user, 'Mine');

    // Join mode builds via DB::table (bypasses Eloquent $hidden), so the column
    // allowlist is the ONLY thing keeping password/uuid out. Default select =
    // the user model's allowlist columns, qualified.
    $rows = $this->service->run([
        'model' => 'user',
        'joins' => [[
            'model' => 'project_member',
            'on' => [['left' => 'users.id', 'operator' => '=', 'right' => 'project_members.user_id']],
        ]],
    ], $this->user);

    expect($rows)->not->toBeEmpty();
    expect($rows[0])->not->toHaveKey('password')
        ->and($rows[0])->not->toHaveKey('remember_token')
        ->and($rows[0])->not->toHaveKey('uuid')
        ->and($rows[0])->toHaveKey('email');
});

it('excludes soft-deleted rows by default and includes them with with_trashed', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    $task = Task::create(['project_id' => $project->id, 'title' => 'Gone', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    $task->delete();

    $without = $this->service->run(['model' => 'task', 'select' => ['title']], $this->user);
    expect($without)->toHaveCount(0);

    $with = $this->service->run(['model' => 'task', 'select' => ['title'], 'with_trashed' => true], $this->user);
    expect(collect($with)->pluck('title')->all())->toBe(['Gone']);
});

it('returns nothing for a user who is a member of no projects', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');

    // A task DOES exist in a project the stranger cannot see, so 0 results
    // genuinely depends on the visibility scope (not just an empty table).
    Task::create(['project_id' => $mine->id, 'title' => 'Hidden', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $stranger = User::factory()->create(['id' => 99]);

    $rows = $this->service->run(['model' => 'task', 'select' => ['title']], $stranger);

    expect($rows)->toHaveCount(0);
});

it('excludes soft-deleted rows in join mode by default and includes them with with_trashed', function () {
    $project = ($this->makeProject)($this->user, 'Mine');
    $task = Task::create(['project_id' => $project->id, 'title' => 'JoinGone', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    $task->delete();

    $join = [[
        'model' => 'ms_task_status',
        'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
    ]];

    $without = $this->service->run(['model' => 'task', 'select' => ['tasks.title'], 'joins' => $join], $this->user);
    expect($without)->toHaveCount(0);

    $with = $this->service->run(['model' => 'task', 'select' => ['tasks.title'], 'joins' => $join, 'with_trashed' => true], $this->user);
    expect(collect($with)->pluck('title')->all())->toBe(['JoinGone']);
});

it('scopes a project-scoped joined table, not just the base', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    ($this->makeProject)($outsider, 'Theirs');

    // Base = user (global) joined to project_members (project-scoped). Acting as
    // $this->user (can see only 'Mine'), the join to project_members must be scoped
    // to visible projects — the outsider's membership in 'Theirs' must be excluded.
    $rows = $this->service->run([
        'model' => 'user',
        'select' => ['users.name', 'project_members.project_id'],
        'joins' => [[
            'model' => 'project_member',
            'on' => [['left' => 'users.id', 'operator' => '=', 'right' => 'project_members.user_id']],
        ]],
    ], $this->user);

    expect(collect($rows)->pluck('project_id')->unique()->values()->all())
        ->toBe([Sqids::encode($mine->id)]);
});

it('does not leak non-allowlisted columns via relation-mode eager loads', function () {
    ($this->makeProject)($this->user, 'Mine');

    // A bare project query auto-eager-loads owner/owned (User models) via
    // Project::$with. Those User models must not leak users.uuid — uuid is a
    // real column NOT covered by Eloquent's $hidden, so only the mcp_query
    // allowlist can keep it out.
    $rows = $this->service->run(['model' => 'project'], $this->user);

    expect($rows)->not->toBeEmpty();

    $json = json_encode($rows);
    expect($json)->not->toContain('uuid')
        ->and($json)->not->toContain('password')
        ->and($json)->not->toContain('remember_token');

    // Projection must not over-strip: safe relation columns still come through.
    expect($rows[0]['owner']['name'])->toBe($this->user->name);
});
