<?php

namespace App\Listeners;

use App\Enums\TaskNotificationType;
use App\Events\TaskCreated;
use App\Facades\TaskNotification;

class NotifyAssignedUsers
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskCreated $event): void
    {
        if (empty($event->assignUserIds)) {
            return;
        }

        try {
            TaskNotification::createTaskNotification(
                $event->task,
                $event->assignUserIds,
                TaskNotificationType::CREATED
            );
        } catch (\Throwable $th) {
            // Fail silently for notification errors
        }
    }
}
