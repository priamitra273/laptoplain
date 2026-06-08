<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository
{
    public function getAssignedRecursive(int $userId)
    {
        $tasks = Task::with([
            'users:id,name',
            'users.media',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'project:id,title',
            'tags:id,name,severity',
            'subTaskRecursive',
            'creator:id,name',
            'creator.media',
        ])
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)->orWhereRelation('users', 'users.id', $userId);
            })
            ->whereNull('parent_id')
            ->whereHas('project')
            ->orderBy('id')
            ->get();

        return $tasks;
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
