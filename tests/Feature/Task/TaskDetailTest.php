<?php

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
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
    ]);

    // "super-admin-" prefix satisfies TaskPolicy::view() and bypasses route.permission middleware.
    Role::create([
        'name' => 'super-admin-admin',
        'guard_name' => 'web',
        'label' => 'Super Admin',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-admin');

    $this->status = MsTaskStatus::query()->orderBy('id')->firstOrFail();
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

function makeDetailTask(int $projectId, array $attrs = []): Task
{
    static $seq = 0;
    $seq++;

    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => $seq,
        'created_by' => 1,
    ], $attrs));
}

it('exposes only attachments-collection media on the task detail prop, shaped as MediaData', function () {
    Storage::fake('public');

    $task = makeDetailTask($this->project->id, [
        'title' => 'Task with files',
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
    ]);

    $task->addMediaFromString('pdf-bytes')->usingFileName('spec.pdf')->toMediaCollection('attachments');
    // A second item in a different collection must NOT leak into the Attachments section.
    $task->addMediaFromString('other-bytes')->usingFileName('ignored.txt')->toMediaCollection('other');

    $response = $this->actingAs($this->user)->get(route('task.show', $task->id));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project/task/Detail')
        ->has('task.media', 1)
        ->where('task.media.0.file_name', 'spec.pdf')
        ->whereType('task.media.0.uuid', 'string')
        ->whereType('task.media.0.url', 'string')
        ->whereType('task.media.0.size', 'integer')
    );
});

it('returns an empty media array when the task has no attachments', function () {
    $task = makeDetailTask($this->project->id, [
        'title' => 'Task without files',
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('task.show', $task->id));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project/task/Detail')
        ->has('task.media', 0)
    );
});

it('returns the parent hierarchy root-first with the current task last (breadcrumb order)', function () {
    $root = makeDetailTask($this->project->id, ['title' => 'Root', 'status_id' => $this->status->id]);
    $middle = makeDetailTask($this->project->id, ['title' => 'Middle', 'status_id' => $this->status->id, 'parent_id' => $root->id]);
    $leaf = makeDetailTask($this->project->id, ['title' => 'Leaf', 'status_id' => $this->status->id, 'parent_id' => $middle->id]);

    $response = $this->actingAs($this->user)->getJson(route('task.parents', $leaf->id));

    $response->assertSuccessful();

    $titles = collect($response->json('data'))->pluck('title')->all();

    expect($titles)->toBe(['Root', 'Middle', 'Leaf']);
});
