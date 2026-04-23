<?php

namespace App\Observers;

use App\Enums\TaskNotificationType;
use App\Facades\TaskNotification;
use App\Models\Task;

class TaskObserver
{
    /**
     * Saat task dibuat atau diupdate
     */
    public function saved(Task $task)
    {
        $this->normalizeProgress($task);
        $this->updateParentTaskProgress($task);
        $this->updateProjectProgress($task);
    }

    public function deleting(Task $task)
    {
        // delete all associative children
        $task->children?->each->delete();
    }

    /**
     * Saat task dihapus (soft delete)
     */
    public function deleted(Task $task)
    {
        $this->updateParentTaskProgress($task);
        $this->updateProjectProgress($task);
        $this->notify($task, TaskNotificationType::DELETED);
    }

    /**
     * Pastikan progress antara 0–100 dan bulatkan 2 desimal
     */
    protected function normalizeProgress(Task $task)
    {
        $progress = round(min(max($task->progress ?? 0, 0), 100), 2);

        if ($task->progress != $progress) {
            $task->updateQuietly(['progress' => $progress]);
        }
    }

    /**
     * Hitung ulang progress project dari semua task utama (parent_id IS NULL)
     */
    protected function updateProjectProgress(Task $task)
    {
        if (! $task->project_id) {
            return;
        }

        $project = $task->project;
        if (! $project) {
            return;
        }

        // Ambil task utama (tidak termasuk subtask)
        $tasks = $project->tasks()
            ->whereNull('parent_id')
            ->where('is_archived', false)
            ->get();

        $averageProgress = $tasks->count() > 0
            ? round($tasks->avg('progress'), 2)
            : 0;

        // Hindari trigger event lain
        $project->updateQuietly(['progress' => $averageProgress]);
    }

    /**
     * Hitung ulang progress task induk berdasarkan subtasks
     */
    protected function updateParentTaskProgress(Task $task)
    {
        $parent = $task->parent;

        if (! $parent) {
            return;
        }

        $children = $parent->children()
            ->where('is_archived', false)
            ->get();

        $averageProgress = $children->count() > 0
            ? round($children->avg('progress'), 2)
            : $parent->status->score;

        $parent->updateQuietly(['progress' => $averageProgress]);

        // Rekursif untuk parent lebih tinggi (nested subtask)
        if ($parent->parent_id) {
            $this->updateParentTaskProgress($parent);
        }
    }

    protected function notify(Task $task, TaskNotificationType $type)
    {
        $users = $task->users->pluck('id')->toArray();

        TaskNotification::createTaskNotification(
            $task,
            $users,
            $type,
        );
    }
}
