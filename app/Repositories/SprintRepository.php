<?php

namespace App\Repositories;

use App\Models\MsSprintStatus;
use App\Models\ProjectSprint;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class SprintRepository
{
    public function getActiveSprintsWithTasks(int $projectId): Collection
    {
        return ProjectSprint::with([
            'status',
            'tasks' => function ($query) {
                $query->where(function ($taskQuery) {
                    $taskQuery->whereNull('parent_id')
                        ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
                });
            },
            'tasks.status:id,name,severity',
            'tasks.priority:id,name,severity',
            'tasks.category:id,name,icon,severity',
            'tasks.users:id,name',
            'tasks.users.media',
        ])
            ->where('project_id', $projectId)
            ->whereNot('sprint_status_id', MsSprintStatus::completed()->id)
            ->orderBy('order')
            ->get();
    }

    public function getBacklogTasks(int $projectId): Collection
    {
        return Task::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name,email',
            'users.media',
        ])
            ->where('project_id', $projectId)
            ->backlog()
            ->where(function ($query) {
                $query->whereNull('parent_id')
                    ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
            })
            ->orderBy('id')
            ->get();
    }

    public function getEpics(int $projectId): Collection
    {
        return Task::with(['status:id,name,severity'])
            ->where('project_id', $projectId)
            ->epics()
            ->get(['id', 'title', 'task_category_id', 'status_id', 'story_points']);
    }

    public function findByProject(int $sprintId, int $projectId): ProjectSprint
    {
        return ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);
    }

    public function getLastOrder(int $projectId): int
    {
        return ProjectSprint::where('project_id', $projectId)->max('order') ?? 0;
    }

    public function create(array $data): ProjectSprint
    {
        return ProjectSprint::create($data);
    }

    public function getIncompleteTaskIds(ProjectSprint $sprint): SupportCollection
    {
        return $sprint->tasks()
            ->whereHas('status', fn ($q) => $q->whereNotIn('name', ['Completed', 'Finished', 'Done']))
            ->pluck('tasks.id');
    }
}
