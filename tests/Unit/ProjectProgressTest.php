<?php

use App\Models\Project;
use App\Models\Task;

test('calculateProgress averages loaded tasks without touching the database', function () {
    $task1 = new Task(['progress' => 0]);
    $task1->setRelation('children', collect([new Task(['progress' => 100])])); // avg 100
    $task2 = new Task(['progress' => 0]);
    $task2->setRelation('children', collect([new Task(['progress' => 0])]));   // avg 0

    $project = new Project();
    $project->setRelation('tasks', collect([$task1, $task2]));

    expect($project->calculateProgress())->toBe(50.0);
});

test('calculateProgress returns 0 when there are no tasks', function () {
    $project = new Project();
    $project->setRelation('tasks', collect());

    expect($project->calculateProgress())->toBe(0.0);
});
