<?php

use App\Actions\Task\RecalculateProgressAction;
use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
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
    // Pin user id 1 so the master seeders' hardcoded FKs resolve (see ProjectTabTest).
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

    $this->status = MsTaskStatus::query()->where('score', '>', 0)->orderBy('score')->firstOrFail();
    $this->priority = MsTaskPriority::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Lazy Write Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

function lazyWriteTask(int $projectId, array $attrs = []): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ], $attrs));
}

it('recalculates ancestor and project progress as the recursive average of children', function () {
    $parent = lazyWriteTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $childA = lazyWriteTask($this->project->id, ['title' => 'A', 'parent_id' => $parent->id, 'progress' => 40]);
    lazyWriteTask($this->project->id, ['title' => 'B', 'parent_id' => $parent->id, 'progress' => 60]);

    (new RecalculateProgressAction)->execute($childA);

    expect((float) $parent->fresh()->progress)->toBe(50.0)
        ->and((float) $this->project->fresh()->progress)->toBe(50.0);
});

it('creates a task via the lazy endpoint and returns success with an encoded id', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        [
            'project_id' => $this->encoded,
            'title' => 'Created via lazy',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'task_category_id' => Sqids::encode($this->category->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    );

    $response->assertSuccessful();
    $response->assertJsonPath('success', true);
    expect($response->json('id'))->toBeString();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $this->project->id,
        'title' => 'Created via lazy',
    ]);
});

it('recalculates parent and project progress when a subtask is created via the lazy endpoint', function () {
    $parent = lazyWriteTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $score = (float) $this->status->score;

    $this->actingAs($this->user)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        [
            'project_id' => $this->encoded,
            'parent_id' => Sqids::encode($parent->id),
            'title' => 'Child via lazy',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    )->assertSuccessful();

    expect((float) $parent->fresh()->progress)->toBe($score)
        ->and((float) $this->project->fresh()->progress)->toBe($score);
});

it('returns 422 when creating a task with invalid data via the lazy endpoint', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        ['project_id' => $this->encoded],
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'status_id', 'priority_id', 'type_id']);
});

it('forbids creating a task for a user without permission via the lazy endpoint', function () {
    $stranger = User::factory()->create(['id' => 777]);

    $response = $this->actingAs($stranger)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        [
            'project_id' => $this->encoded,
            'title' => 'Nope',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    );

    $response->assertForbidden();
});
