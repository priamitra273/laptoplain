<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTaskPriority
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task_priority')
            ->logOnly(['name', 'severity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create task priority',
            'updated'  => 'Update task priority',
            'deleted'  => 'Delete task priority',
            'restored' => 'Restore task priority',
            default    => $eventName,
        };
    }
}
