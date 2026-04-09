<?php

namespace App\Listeners;

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
    public function handle(\App\Events\TaskCreated $event): void
    {
        if (empty($event->assignUserIds)) {
            return;
        }

        try {
            \App\Facades\TaskNotification::createTaskNotification(
                $event->task,
                $event->assignUserIds,
                \App\Enums\TaskNotificationType::CREATED
            );
        } catch (\Throwable $th) {
            // Fail silently for notification errors
        }
    }
}
