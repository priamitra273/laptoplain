<?php

namespace App\Services;

use App\Enums\TaskNotificationType;
use App\Facades\TaskNotification;
use App\Models\MsTaskStatus;
use App\Models\Task;

class TaskService
{
    public function updateStatus(Task $task, MsTaskStatus $status): void
    {
        $taskHasChildren = $task->children()->exists();

        $completedStatusId = MsTaskStatus::where('name', 'Completed')->value('id');

        $data = [
            'status_id' => $status->id,
        ];

        if ((int) $status->id === (int) $completedStatusId) {
            $data['completed_at'] = now();

            if (! $taskHasChildren) {
                $data['progress'] = 100;
            }
        } else {
            $data['completed_at'] = null;

            if (! $taskHasChildren) {
                $data['progress'] = $status->score;
            }
        }

        $task->update($data);

        if (! $taskHasChildren) {
            $this->calculateParentProgress($task);
        }

        $this->dispatchNotification($task);
    }

    protected function calculateParentProgress(Task $task): void
    {
        $parent = $task->parent;

        while ($parent) {
            $parent->update(['progress' => $parent->calculateProgress()]);
            $parent = $parent->parent;
        }
    }

    protected function dispatchNotification(Task $task): void
    {
        try {
            $user_ids = $task->users()->whereNotNull('users.id')->get()->pluck('users.id')->toArray();

            TaskNotification::createTaskNotification(
                $task,
                $user_ids,
                TaskNotificationType::UPDATED
            );
        } catch (\Throwable $th) {
            // throw $th;
        }
    }
}
