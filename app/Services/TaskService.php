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
use App\Models\User;
use App\Repositories\TaskRepository;

class TaskService
{
    public function __construct(
        protected TaskRepository $repository
    ) {}

    /**
     * Get task by id for detail view
     */
    public function getTaskForDetail(int $taskId, int $userId): Task
    {
        return $this->repository->findByIdForDetail($taskId, $userId);
    }

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

        $projects = Project::visibleFor(User::find($userId))
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

    public function updateStatus(Task $task, MsTaskStatus $status, ?string $due_date): void
    {
        $taskHasChildren = $task->children()->exists();

        $completedStatusId = MsTaskStatus::where('name', 'Completed')->value('id');

        $data = [
            'status_id' => $status->id,
            'due_date' => $due_date,
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

    public function updateParent(Task $task, ?int $parentId): void
    {
        $oldParent = $task->parent;

        if ($oldParent) {
            $hasSiblings = $oldParent->children()->where('id', '!=', $task->id)->exists();
            $oldParentForProgress = $hasSiblings ? $oldParent->children()->where('id', '!=', $task->id)->first() : $oldParent;
        } else {
            $oldParentForProgress = null;
        }

        $task->update([
            'parent_id' => $parentId,
        ]);

        $this->calculateParentProgress($task);

        if ($oldParentForProgress) {
            $this->calculateParentProgress($oldParentForProgress);
        }
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

    /**
     * Get task details with all required properties for the detail view
     */
    public function getTaskDetailProps(Task $task, \App\Models\User $user): array
    {
        $task->update(['progress' => $task->calculateProgress()]);

        $project = $task->project;

        $assignableUsers = $this->formatAssignableUsers($project->projectMembers);
        $assignedUsers = $this->formatAssignedUsers($task->users);
        $creator = $this->formatCreator($task->creator);

        $isOwner = $this->isProjectOwner($project->projectMembers, $user);
        $isTaskMember = $task->users->contains('id', $user->id);

        return [
            'task' => $task->toArray(),
            'project' => $project->toArray(),
            'assignedUsers' => $assignedUsers,
            'assignableUsers' => $assignableUsers,
            'creator' => $creator,
            'statuses' => MsTaskStatus::select('id', 'name', 'severity')->get()->toArray(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get()->toArray(),
            'types' => MsTaskType::select('id', 'name', 'severity')->get()->toArray(),
            'categories' => TaskCategory::select('id', 'name', 'icon', 'severity')->get()->toArray(),
            'isTaskMember' => $isTaskMember,
            'isOwner' => $isOwner,
            'comments' => $task->comments?->toArray() ?? [],
        ];
    }

    protected function formatAssignableUsers($projectMembers): array
    {
        return collect($projectMembers)
            ->filter(fn ($member) => $member->user !== null)
            ->map(fn ($member) => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'avatar_url' => $member->user->avatar_url,
            ])
            ->unique('id')
            ->values()
            ->toArray();
    }

    protected function formatAssignedUsers($users): array
    {
        return collect($users)
            ->filter(fn ($user) => $user !== null)
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url ?? null,
            ])
            ->values()
            ->toArray();
    }

    protected function formatCreator(User $creator): ?array
    {
        if (! $creator) {
            return null;
        }

        return [
            'id' => $creator->id,
            'name' => $creator->name,
            'avatar_url' => $creator->avatar_url ?? null,
        ];
    }

    protected function isProjectOwner($projectMembers, \App\Models\User $user): bool
    {
        return collect($projectMembers)->contains(function ($member) use ($user) {
            return $member->user_id === $user->id
                && $member->role?->name === 'Owner';
        });
    }
}
