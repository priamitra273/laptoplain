<?php

namespace App\Services;

use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TaskNotificationService
{
    private function getMessage(string $taskTitle, TaskNotificationType $type): string
    {
        switch ($type) {
            case TaskNotificationType::MENTIONED:
                return "You were mentioned in task '{$taskTitle}' ";
            default:
                return "Task '{$taskTitle}' has been {$type->message()}.";
        }
    }

    public function createTaskNotification($task, $userIds, TaskNotificationType $type)
    {
        $watchersIds = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['watcher-admin']);
        })->pluck('id')->toArray();

        $userIds = array_unique(array_merge($userIds, $watchersIds));

        $notification = Notification::create([
            'task_id' => $task->id,
            'task_status_id' => $task->status_id,
            'task_type_id' => $task->type_id,
            'message' => $this->getMessage($task->title, $type),
        ]);

        foreach ($userIds as $userId) {
            $notification->users()->attach($userId, ['is_read' => false]);

            $payload = [
                'id' => Sqids::encode($notification->id),
                'message' => $notification->message,
                'task_id' => Sqids::encode($notification->task_id),
                'is_read' => false,
            ];

            $key = "notifications:user:$userId";
            $existing = Cache::store('redis')->get($key, []);
            $existing[] = $payload;

            Cache::store('redis')->put(
                $key,
                $existing,
                now()->addMinutes(1)
            );
        }
    }
}
