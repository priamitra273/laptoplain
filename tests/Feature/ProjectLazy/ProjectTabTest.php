<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsSprintStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectSprint;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsSprintStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;

beforeEach(function () {
    // See ProjectDetailTaskTreeTest: pin user id 1 so the master seeders' hardcoded
    // owned_id/created_by FKs resolve under Postgres' non-transactional sequences.
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
        MsSprintStatusSeeder::class,
    ]);

    // Super admin bypasses route.permission and gets a full-access policy.
    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $this->status = MsTaskStatus::query()->firstOrFail();
    $this->priority = MsTaskPriority::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Lazy Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

function lazyTask(int $projectId, array $attrs = []): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ], $attrs));
}

function seedActiveSprintTask(): Task
{
    $sprint = ProjectSprint::create([
        'project_id' => test()->project->id,
        'sprint_status_id' => MsSprintStatus::active()->id,
        'name' => 'Sprint 1',
        'order' => 1,
    ]);

    $task = lazyTask(test()->project->id, [
        'title' => 'Board Task',
        'status_id' => test()->status->id,
        'priority_id' => test()->priority->id,
        'type_id' => test()->type->id,
        'task_category_id' => test()->category->id,
    ]);
    $task->sprints()->attach($sprint->id);

    return $task;
}

it('kanban tab defers the board payload behind the cheap shell, then resolves it (with encoded ids) on the follow-up request', function () {
    seedActiveSprintTask();

    $response = $this->actingAs($this->user)->get("/project/{$this->encoded}/kanban");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project-lazy/Kanban')
        // cheap shell paints immediately
        ->has('project.title')
        ->has('policy')
        // the board payload is deferred — absent from the first render
        ->missing('tasks')
        ->missing('taskStatuses')
        ->missing('taskPriorities')
        ->missing('taskTypes')
        ->missing('taskCategories')
        ->missing('tags')
        ->missing('assignableUsers')
        ->missing('epics')
        // ...and fetched together as one deferred group on the follow-up request
        ->loadDeferredProps('kanban', fn ($page) => $page
            ->has('tasks', 1)
            ->where('tasks.0.title', 'Board Task')
            // ids are sqid strings, not raw integers (encoded inside the deferred closure)
            ->whereType('tasks.0.id', 'string')
            ->has('tasks.0.status.id')
            ->has('taskCategories')
            ->has('tags')
            ->has('assignableUsers')
            ->has('epics')
        )
    );
});

it('list tab returns a slim task tree (no description/priority/media) and only filter options', function () {
    $root = lazyTask($this->project->id, [
        'title' => 'Root',
        'status_id' => $this->status->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
        'description' => 'should not leak to the list payload',
    ]);
    lazyTask($this->project->id, ['title' => 'Child', 'parent_id' => $root->id]);

    $response = $this->actingAs($this->user)->get("/project/{$this->encoded}/list");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project-lazy/List')
        ->has('tasks', 1)
        ->has('tasks.0.sub_task_recursive', 1)
        ->has('tasks.0.status.name')
        ->has('taskStatuses')
        ->has('taskTypes')
        // form option lists are bundled with the tab (small master tables)
        ->has('taskCategories')
        ->has('tags')
        // slim task shape: heavy per-task fields are intentionally absent
        ->missing('tasks.0.description')
        ->missing('tasks.0.priority')
        ->missing('tasks.0.media')
        ->missing('tasks.0.tags')
        // bulk data belonging to other tabs must NOT be sent
        ->missing('sprints')
        ->missing('backlog')
        ->missing('roles')
    );
});

it('team tab returns members, roles and assignable users — not tasks', function () {
    $ownerRole = MsProjectRole::where('name', 'Owner')->firstOrFail();

    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => $ownerRole->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->get("/project/{$this->encoded}/team");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project-lazy/Team')
        ->has('members', 1)
        ->where('members.0.role.name', 'Owner')
        ->has('members.0.user.email')
        ->has('roles')
        ->has('users')
        ->missing('tasks')
        ->missing('sprints')
    );
});

it('shell members carry a slim user and exclude members whose user is inactive', function () {
    $memberRole = MsProjectRole::query()->firstOrFail();

    $activeUser = User::factory()->create(['id' => 900, 'name' => 'Active Member', 'is_active' => true]);
    $inactiveUser = User::factory()->create(['id' => 901, 'name' => 'Inactive Member', 'is_active' => false]);

    foreach ([$activeUser, $inactiveUser] as $member) {
        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $member->id,
            'project_role_id' => $memberRole->id,
            'owned_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    $response = $this->actingAs($this->user)->get("/project/{$this->encoded}/detail");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project-lazy/Detail')
        // the inactive user's member is filtered out at the query level (findShell),
        // so the redundant null-user guard in shellData is unnecessary
        ->has('members', 1)
        ->where('members.0.user.name', 'Active Member')
        ->has('members.0.id')
        ->has('members.0.user.id')
        ->has('members.0.user.avatar_url')
    );
});

it('task edit endpoint returns the full task payload as JSON', function () {
    $task = lazyTask($this->project->id, [
        'title' => 'Editable',
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
        'description' => 'detail body',
    ]);
    $taskEncoded = Sqids::encode($task->id);

    $response = $this->actingAs($this->user)
        ->getJson("/project/{$this->encoded}/tasks/{$taskEncoded}/edit");

    $response->assertSuccessful();
    $response->assertJsonPath('data.title', 'Editable');
    $response->assertJsonPath('data.description', 'detail body');

    expect($response->json('data.status'))->not->toBeNull()
        ->and($response->json('data.priority'))->not->toBeNull()
        ->and($response->json('data.type'))->not->toBeNull();
});
