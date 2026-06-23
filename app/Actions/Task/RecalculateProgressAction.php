<?php

namespace App\Actions\Task;

use App\Models\Task;

class RecalculateProgressAction
{
    public function execute(Task $task): void
    {
        $parent = $task->parent;

        while ($parent) {
            $parent->update(['progress' => $parent->calculateProgress()]);
            $parent = $parent->parent;
        }

        $task->project->update(['progress' => $task->project->calculateProgress()]);
    }
}
