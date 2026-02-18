<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTaskType
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task_type')
            ->logOnly([
                'name',
                'severity',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create task type',
            'updated'  => 'Update task type',
            'deleted'  => 'Delete task type',
            'restored' => 'Restore task type',
            default    => $eventName,
        };
    }
}
