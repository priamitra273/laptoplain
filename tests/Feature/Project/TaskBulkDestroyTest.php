<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectStatusSeeder;

beforeEach(function () {
    // Master seeders hardcode owned_id/created_by = 1; pin the user to id 1.
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
    ]);

    // Super-admin grants the route.permission middleware bypass + full-access policy.
    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $this->project = makeBulkProject($this->user);
});

function makeBulkProject(User $user): Project
{
    return Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $user->id,
        'owned_id' => $user->id,
        'title' => 'Bulk Project',
        'emoji' => '🧹',
        'progress' => 0,
    ]);
}

function makeBulkTask(int $projectId): Task
{
    return Task::create([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ]);
}

it('deletes every selected task that belongs to the project', function () {
    $a = makeBulkTask($this->project->id);
    $b = makeBulkTask($this->project->id);
    $encoded = Sqids::encode($this->project->id);

    $response = $this->actingAs($this->user)->deleteJson("/project/{$encoded}/tasks", [
        'ids' => [Sqids::encode($a->id), Sqids::encode($b->id)],
    ]);

    $response->assertOk()->assertJson(['success' => true, 'deleted' => 2, 'failed' => 0]);
    $this->assertSoftDeleted('tasks', ['id' => $a->id]);
    $this->assertSoftDeleted('tasks', ['id' => $b->id]);
});

it('reports a partial failure and leaves tasks from other projects untouched', function () {
    $mine = makeBulkTask($this->project->id);
    $foreign = makeBulkTask(makeBulkProject($this->user)->id);

    $encoded = Sqids::encode($this->project->id);

    $response = $this->actingAs($this->user)->deleteJson("/project/{$encoded}/tasks", [
        'ids' => [Sqids::encode($mine->id), Sqids::encode($foreign->id), 'not-a-real-id'],
    ]);

    $response->assertOk()->assertJson(['success' => true, 'deleted' => 1, 'failed' => 2]);
    $this->assertSoftDeleted('tasks', ['id' => $mine->id]);
    $this->assertNotSoftDeleted('tasks', ['id' => $foreign->id]);
});

it('rejects an empty selection', function () {
    $encoded = Sqids::encode($this->project->id);

    $this->actingAs($this->user)
        ->deleteJson("/project/{$encoded}/tasks", ['ids' => []])
        ->assertUnprocessable();
});
