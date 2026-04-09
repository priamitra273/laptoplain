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

    public function findByIdForDetail(mixed $taskId, int $userId): Task
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
                        'replies' => function ($q) {
                            $q->orderBy('id', 'asc');
                        },
                        'replies.user',
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
}
