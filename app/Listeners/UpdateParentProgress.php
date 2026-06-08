<?php

namespace App\Listeners;

use App\Events\TaskCreated;

class UpdateParentProgress
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskCreated $event): void
    {
        $task = $event->task;
        $parent = $task->parent;

        while ($parent) {
            $parent->update([
                'progress' => $parent->calculateProgress(),
            ]);

            $parent = $parent->parent;
        }
    }
}
