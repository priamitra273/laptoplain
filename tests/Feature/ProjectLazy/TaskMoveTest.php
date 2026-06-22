<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\MsTaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded FKs resolve (see LazyTaskWriteTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
    ]);

    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $this->status = MsTaskStatus::query()->where('score', '>', 0)->orderBy('score')->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Move Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

function moveTask(int $projectId, array $attrs = []): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
    ], $attrs));
}

function moveRoute(string $encoded, Task $task): string
{
    return route('project.tasks.move', [
        'projectEncoded' => $encoded,
        'task' => Sqids::encode($task->id),
    ]);
}

it('reorders a root sibling to the end and normalizes sequence numbers', function () {
    $a = moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 0]);
    $b = moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 1]);
    $c = moveTask($this->project->id, ['title' => 'C', 'sequence_number' => 2]);

    // siblings excluding A = [B, C]; insert at index 2 -> [B, C, A]
    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null, 'position' => 2])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect((int) $b->fresh()->sequence_number)->toBe(0)
        ->and((int) $c->fresh()->sequence_number)->toBe(1)
        ->and((int) $a->fresh()->sequence_number)->toBe(2);
});

it('reorders a root sibling to the front', function () {
    $a = moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 0]);
    $b = moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 1]);
    $c = moveTask($this->project->id, ['title' => 'C', 'sequence_number' => 2]);

    // siblings excluding C = [A, B]; insert at index 0 -> [C, A, B]
    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $c), ['parent_id' => null, 'position' => 0])
        ->assertSuccessful();

    expect((int) $c->fresh()->sequence_number)->toBe(0)
        ->and((int) $a->fresh()->sequence_number)->toBe(1)
        ->and((int) $b->fresh()->sequence_number)->toBe(2);
});

it('nests a task under a target and recalculates parent progress', function () {
    $parent = moveTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $movable = moveTask($this->project->id, [
        'title' => 'Movable',
        'status_id' => $this->status->id,
        'progress' => (float) $this->status->score,
    ]);
    $score = (float) $this->status->score;

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $movable), [
            'parent_id' => Sqids::encode($parent->id),
            'position' => 0,
        ])
        ->assertSuccessful();

    expect((int) $movable->fresh()->parent_id)->toBe($parent->id)
        ->and((int) $movable->fresh()->sequence_number)->toBe(0)
        ->and((float) $parent->fresh()->progress)->toBe($score);
});

it('moves a child back to the root level', function () {
    $parent = moveTask($this->project->id, ['title' => 'Parent']);
    $child = moveTask($this->project->id, ['title' => 'Child', 'parent_id' => $parent->id]);

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $child), ['parent_id' => null, 'position' => 0])
        ->assertSuccessful();

    expect($child->fresh()->parent_id)->toBeNull();
});

it('clamps an out-of-range position to the end of the sibling group', function () {
    $a = moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 0]);
    $b = moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 1]);

    // siblings excluding A = [B]; position 999 clamps to 1 -> [B, A]
    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null, 'position' => 999])
        ->assertSuccessful();

    expect((int) $b->fresh()->sequence_number)->toBe(0)
        ->and((int) $a->fresh()->sequence_number)->toBe(1);
});

it('rejects moving a task under its own descendant', function () {
    $a = moveTask($this->project->id, ['title' => 'A']);
    $b = moveTask($this->project->id, ['title' => 'B', 'parent_id' => $a->id]);

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), [
            'parent_id' => Sqids::encode($b->id),
            'position' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

it('requires a position', function () {
    $a = moveTask($this->project->id, ['title' => 'A']);

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['position']);
});

it('forbids moving a task for a user without permission', function () {
    $stranger = User::factory()->create(['id' => 777]);
    $a = moveTask($this->project->id, ['title' => 'A']);

    $this->actingAs($stranger)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null, 'position' => 0])
        ->assertForbidden();
});

it('orders sibling tasks by sequence_number with nulls last then id', function () {
    // ids ascending (A < B < C); sequence_number arranges them B, C, A
    moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 2]);
    moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 0]);
    moveTask($this->project->id, ['title' => 'C', 'sequence_number' => 1]);

    $tree = app(\App\Repositories\ProjectRepository::class)->getTaskTree($this->project->id)->toArray();

    expect(collect($tree)->pluck('title')->all())->toBe(['B', 'C', 'A']);
});

it('falls back to id order when sequence_number is null', function () {
    moveTask($this->project->id, ['title' => 'A']); // sequence_number null
    moveTask($this->project->id, ['title' => 'B']); // sequence_number null

    $tree = app(\App\Repositories\ProjectRepository::class)->getTaskTree($this->project->id)->toArray();

    expect(collect($tree)->pluck('title')->all())->toBe(['A', 'B']);
});
