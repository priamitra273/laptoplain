<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

class TaskRepository
{
    /**
     * Base query for the "My Task" index: root-level tasks assigned to (or created by)
     * the user, with the slim relations + aggregate subtask counts the view needs.
     *
     * Returns a Builder so callers decide ordering + pagination (list) or limit (board).
     *
     * @param  array<string, mixed>  $filters  decoded (int) filter values
     */
    public function assignedTasksQuery(int $userId, array $filters = []): Builder
    {
        $query = Task::query()
            ->with([
                'users:id,name,email',
                'users.media',
                'status:id,name,severity',
                'priority:id,name,severity',
                'type:id,name,severity',
                'project:id,title',
            ])
            ->withCount([
                'children as sub_task_count',
                'children as sub_task_done_count' => fn ($q) => $q->whereNotNull('completed_at'),
            ])
            ->where(fn (Builder $q) => $this->scopeAssigned($q, $userId))
            ->where(function (Builder $q) {
                $q->whereRelation('category', 'task_categories.name', '!=', 'Epic')
                    ->orWhereNull('task_category_id');
            })
            ->whereDoesntHave('children')
            ->whereHas('project');

        $this->applyTaskFilters($query, $filters);

        return $query;
    }

    /**
     * Count assigned root tasks grouped by status, for the status-summary chips.
     * Respects every filter except status (so all status chips remain visible).
     * Filtering only task, not the parent task
     *
     * @param  array<string, mixed>  $filters  decoded (int) filter values
     * @return SupportCollection<int, int> keyed by status_id => count
     */
    public function assignedStatusCounts(int $userId, array $filters = []): SupportCollection
    {
        unset($filters['status_id']);

        $query = Task::query()
            ->where(fn (Builder $q) => $this->scopeAssigned($q, $userId))
            ->where(function (Builder $q) {
                $q->whereRelation('category', 'task_categories.name', '!=', 'Epic')
                    ->orWhereNull('task_category_id');
            })
            ->whereDoesntHave('children')
            ->whereHas('project');

        $this->applyTaskFilters($query, $filters);

        return $query->selectRaw('status_id, count(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');
    }

    protected function scopeAssigned(Builder $query, int $userId): void
    {
        $query->where('created_by', $userId)
            ->orWhereRelation('users', 'users.id', $userId);
    }

    /**
     * @param  array<string, mixed>  $filters  decoded (int) filter values
     */
    protected function applyTaskFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->where('title', 'ilike', '%'.$filters['search'].'%');
        }

        foreach (['project_id', 'status_id', 'priority_id', 'type_id'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }
    }

    public function findByIdForDetail(int $taskId, int $userId): Task
    {
        return Task::with([
            'project:id,title,emoji',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'users:id,name',
            'users.media',
            'tags:id,name,severity',
            'subTaskRecursive',
            'subTaskRecursive.status:id,name,severity',
            'subTaskRecursive.priority:id,name,severity',
            'subTaskRecursive.type:id,name,severity',
            'subTaskRecursive.category:id,name,icon,severity',
            'subTaskRecursive.users:id,name',
            'creator:id,name', // Add creator relationship
            'creator.media',
            'media' => fn ($q) => $q->where('collection_name', 'attachments'),
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->orderBy('id', 'asc')
                    ->with([
                        'user',
                        'replies' => fn ($q) => $q->orderBy('id', 'asc'),
                        'replies.user',
                        'replies.reaction_group_count',
                        'reaction_group_count',
                    ]);
            },
        ])->withExists([
            'project as is_project_member' => function ($q) use ($userId) {
                $q->whereHas('projectMembers', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            },
        ])->findOrFail($taskId);
    }

    /**
     * Get comments for a task
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, App\Models\Comment>
     */
    public function getComments(Task $task)
    {
        return $task->comments()
            ->whereNull('parent_id')
            ->orderBy('id', 'asc')
            ->with([
                'user',
                'replies' => fn ($q) => $q->orderBy('id', 'asc'),
                'replies.user',
                'replies.reaction_group_count',
                'reaction_group_count',
            ])
            ->get();
    }

    public function getParents(int $taskId)
    {
        $query = "
            WITH RECURSIVE ParentHierarchy AS (
                SELECT tasks.*,
                    CONCAT(projects.code, '-', tasks.sequence_number) as key,
                    tc.name as task_category_name,
                    tc.icon as task_category_icon,
                    tc.severity as task_category_severity,
                    1 as depth
                FROM tasks
                INNER JOIN projects ON tasks.project_id = projects.id
                LEFT JOIN task_categories tc ON tasks.task_category_id = tc.id
                WHERE tasks.id = :id

                UNION ALL

                SELECT c.*,
                    CONCAT(projects.code, '-', c.sequence_number) as key,
                    tc.name as task_category_name,
                    tc.icon as task_category_icon,
                    tc.severity as task_category_severity,
                    ph.depth + 1
                FROM tasks c
                INNER JOIN projects ON c.project_id = projects.id
                LEFT JOIN task_categories tc ON c.task_category_id = tc.id
                INNER JOIN ParentHierarchy ph ON c.id = ph.parent_id
            )
            SELECT * FROM ParentHierarchy
            ORDER BY depth DESC;
        ";

        return Task::fromQuery($query, ['id' => $taskId]);
    }

    /**
     * Get activity logs for a task
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivities(int $taskId, ?string $event = null)
    {
        $query = \Spatie\Activitylog\Models\Activity::query()
            ->with('causer:id,name,email')
            ->where('subject_type', Task::class)
            ->where('subject_id', $taskId)
            ->orderByDesc('created_at');

        if ($event !== null) {
            $query->where('event', $event);
        }

        return $query->get();
    }
}
