<?php

use App\Data\Task\ProjectTaskData;
use App\Models\MsTaskStatus;
use App\Models\Task;
use Spatie\LaravelData\DataCollection;

uses(Tests\TestCase::class);

function makeLeafTask(array $attrs = []): Task
{
    $task = new Task(array_merge([
        'title' => 'Leaf',
        'progress' => 10,
        'sequence_number' => 1,
        'is_archived' => false,
    ], $attrs));
    $task->id = $attrs['id'] ?? 1;
    $task->status_id = $attrs['status_id'] ?? 1;
    $task->priority_id = $attrs['priority_id'] ?? 1;
    $task->type_id = $attrs['type_id'] ?? 1;
    $task->project_id = $attrs['project_id'] ?? 1;
    // Relasi di-set manual agar fromModel tidak menyentuh DB.
    $task->setRelation('users', collect());
    $task->setRelation('tags', collect());
    $task->setRelation('media', collect());

    return $task;
}

function emptyChildren(): DataCollection
{
    return ProjectTaskData::collect([], DataCollection::class);
}

it('maps scalar fields and casts ids/progress', function () {
    $task = makeLeafTask(['id' => 7, 'title' => 'Hello', 'progress' => 42]);

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->id)->toBe(7)
        ->and($data->title)->toBe('Hello')
        ->and($data->progress)->toBe(42.0)
        ->and($data->is_archived)->toBeFalse();
});

it('marks completed task with completed_at and overdue rule', function () {
    $task = makeLeafTask([
        'due_date' => '2026-01-01',
    ]);
    $task->updated_at = \Carbon\Carbon::parse('2026-01-05 10:00:00');
    $status = new MsTaskStatus(['name' => 'Completed']);
    $status->id = 1;
    $task->setRelation('status', $status);

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->completed_at)->not->toBeNull()
        // updated_at (Jan 5) > due_date end-of-day (Jan 1) → overdue
        ->and($data->is_overdue)->toBeTrue();
});

it('computes overdue for non-completed past-due task', function () {
    $task = makeLeafTask(['due_date' => '2020-01-01']);
    $status = new MsTaskStatus(['name' => 'In Progress']);
    $status->id = 1;
    $task->setRelation('status', $status);

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->completed_at)->toBeNull()
        ->and($data->is_overdue)->toBeTrue();
});

it('treats a task without a due date as not overdue (parity)', function () {
    $task = makeLeafTask(); // no due_date
    $task->setRelation('status', tap(new MsTaskStatus(['name' => 'In Progress']), fn ($s) => $s->id = 1));

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->is_overdue)->toBeFalse()
        ->and($data->completed_at)->toBeNull();
});

it('sets sub_task and sub_task_recursive to the same children collection', function () {
    $parent = makeLeafTask(['id' => 1]);
    $childData = ProjectTaskData::fromModel(makeLeafTask(['id' => 2, 'parent_id' => 1]), emptyChildren());
    $children = ProjectTaskData::collect([$childData], DataCollection::class);

    $data = ProjectTaskData::fromModel($parent, $children);

    expect($data->sub_task)->toBe($data->sub_task_recursive)
        ->and($data->sub_task->count())->toBe(1);
});
