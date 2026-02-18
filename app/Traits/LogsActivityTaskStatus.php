<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTaskStatus
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task_status')
            ->logOnly([
                'name',
                'severity',
                'score',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create task status',
            'updated'  => 'Update task status',
            'deleted'  => 'Delete task status',
            'restored' => 'Restore task status',
            default    => $eventName,
        };
    }
}
