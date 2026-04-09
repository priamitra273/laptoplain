<?php

namespace App\Services;

use App\Enums\TaskNotificationType;
use App\Facades\TaskNotification;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Repositories\TaskRepository;

class TaskService
{
    public function __construct(
        protected TaskRepository $repository
    ) {}

    /**
     * Get props for task index page
     */
    public function indexProps(int $userId): array
    {
        $tasks = $this->repository->getAssignedRecursive($userId);

        $totalAssigned = $tasks->where('is_assigned', true)->count();

        $statuses = MsTaskStatus::select('id', 'name', 'severity')->orderBy('id')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();
        $categories = TaskCategory::select('id', 'name', 'icon', 'severity')->get();

        $projects = Project::visibleFor($userId)
            ->select('id', 'title')
            ->get();

        $props = [
            'tasks' => $tasks->toArray(),
            'statuses' => $statuses->toArray(),
            'priorities' => $priorities->toArray(),
            'types' => $types->toArray(),
            'categories' => $categories->toArray(),
            'projects' => $projects->toArray(),
            'totalAssigned' => $totalAssigned,
        ];

        return $props;
    }

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
