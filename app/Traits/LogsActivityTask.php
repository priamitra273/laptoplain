<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTask
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task')
            ->logOnly([
                'project_id',
                'parent_id',
                'status_id',
                'priority_id',
                'type_id',
                'owned_id',
                'title',
                'description',
                'start_date',
                'due_date',
                'progress',
                'is_archived',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create task',
            'updated'  => 'Update task',
            'deleted'  => 'Delete task',
            'restored' => 'Restore task',
            default    => $eventName,
        };
    }
}
