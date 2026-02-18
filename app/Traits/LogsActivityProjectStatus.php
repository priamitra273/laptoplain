<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityProjectStatus
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_status')
            ->logOnly(['name', 'severity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Create project status',
            'updated' => 'Update project status',
            'deleted' => 'Delete project status',
            'restored' => 'Restore project status',
            default   => $eventName,
        };
    }
}
