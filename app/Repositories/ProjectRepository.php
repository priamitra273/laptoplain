<?php

namespace App\Repositories;

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsSprintStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectRepository
{
    /**
     * Find a project by its integer ID.
     */
    public function findById(int $id): Project
    {
        return Project::findOrFail($id);
    }

    /**
     * Get all projects visible to the given user, with status and priority.
     */
    public function getAllVisibleForUser(User $user): Collection
    {
        return Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
        ])
            ->visibleFor($user)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Find a project by encoded ID and eager-load all relations needed for the show page.
     *
     * @throws NotFoundHttpException
     */
    public function findWithRelationsForShow(string $encoded): Project
    {
        try {
            $projectId = Sqids::decode($encoded);

            return Project::with([
                'status:id,name,severity',
                'priority:id,name,severity',
                'projectMembers' => function ($query) {
                    $query->whereHas('user');
                },
                'projectMembers.user:id,name,email',
                'projectMembers.user.media',
                'projectMembers.role:id,name',
                'tasks' => function ($query) {
                    $query->withRecursive();
                },
            ])->findOrFail($projectId);
        } catch (\Exception $e) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Get users who are not yet members of the project, with their media.
     */
    public function getAvailableUsers(SupportCollection $memberUserIds): Collection
    {
        return User::whereNotIn('id', $memberUserIds)
            ->with('media')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Get active sprints for a project, with tasks and user media.
     */
    public function getActiveSprints(int $projectId): Collection
    {
        return ProjectSprint::with([
            'status:id,name,severity',
            'tasks' => function ($q) {
                $q->with([
                    'status:id,name,severity',
                    'priority:id,name,severity',
                    'type:id,name,severity',
                    'category:id,name,icon,severity',
                    'users:id,name',
                    'users.media',
                ])
                    ->where(function ($taskQuery) {
                        $taskQuery->whereNull('parent_id')
                            ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
                    })
                    ->orderBy('id');
            },
        ])
            ->where('project_id', $projectId)
            ->whereNot('sprint_status_id', MsSprintStatus::completed()->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get backlog tasks (not in any sprint) for a project, with user media.
     */
    public function getBacklogTasks(int $projectId): Collection
    {
        return Task::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name',
            'users.media',
        ])
            ->where('project_id', $projectId)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
            })
            ->doesntHave('sprints')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all epics for a project.
     */
    public function getEpics(int $projectId): Collection
    {
        return Task::epics()
            ->where('project_id', $projectId)
            ->get(['id', 'title', 'story_points']);
    }

    public function getTaskStatuses(): Collection
    {
        return MsTaskStatus::select('id', 'name', 'severity', 'score')->get();
    }

    public function getTaskPriorities(): Collection
    {
        return MsTaskPriority::select('id', 'name', 'severity')->get();
    }

    public function getTaskTypes(): Collection
    {
        return MsTaskType::select('id', 'name', 'severity')->get();
    }

    public function getTags(): Collection
    {
        return Tag::select('id', 'name', 'severity')->get();
    }

    public function getProjectRoles(): Collection
    {
        return MsProjectRole::all(['id', 'name']);
    }

    public function getProjectStatuses(): Collection
    {
        return MsProjectStatus::select('id', 'name', 'severity')->get();
    }

    public function getProjectPriorities(): Collection
    {
        return MsProjectPriority::select('id', 'name', 'severity')->get();
    }

    public function getTaskCategories(): Collection
    {
        return TaskCategory::select('id', 'name', 'icon', 'severity')->orderBy('severity')->get();
    }
}
