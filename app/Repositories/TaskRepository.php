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
}
