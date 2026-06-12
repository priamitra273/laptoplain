<?php

use App\Data\Task\ProjectTaskData;
use App\Models\Task;

uses(Tests\TestCase::class);

function flatTask(int $id, ?int $parentId, int $seq): Task
{
    $task = new Task(['title' => "T{$id}", 'progress' => 0, 'sequence_number' => $seq, 'is_archived' => false]);
    $task->id = $id;
    $task->parent_id = $parentId;
    $task->project_id = 1;
    $task->setRelation('users', collect());
    $task->setRelation('tags', collect());
    $task->setRelation('media', collect());

    return $task;
}

it('assembles a 3-level tree from a flat ordered collection', function () {
    // root(1) → child(2) → grandchild(3); plus second root(4)
    $flat = collect([
        flatTask(1, null, 1),
        flatTask(4, null, 2),
        flatTask(2, 1, 1),
        flatTask(3, 2, 1),
    ]);

    $tree = ProjectTaskData::treeFromTasks($flat);

    expect($tree)->toHaveCount(2);

    $root = $tree->toCollection()->firstWhere('id', 1);
    expect($root->sub_task_recursive)->toHaveCount(1);

    $child = $root->sub_task_recursive->toCollection()->first();
    expect($child->id)->toBe(2)
        ->and($child->sub_task_recursive)->toHaveCount(1)
        ->and($child->sub_task_recursive->toCollection()->first()->id)->toBe(3);
});

it('keeps roots ordered by input order', function () {
    $flat = collect([flatTask(10, null, 1), flatTask(20, null, 2)]);

    $tree = ProjectTaskData::treeFromTasks($flat);

    expect($tree->toCollection()->pluck('id')->all())->toBe([10, 20]);
});

it('returns empty collection for empty input', function () {
    expect(ProjectTaskData::treeFromTasks(collect()))->toHaveCount(0);
});
