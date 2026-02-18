<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityProject
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project')
            ->logOnly([
                'project_no',
                'status_id',
                'priority_id',
                'owner_id',
                'owned_id',
                'title',
                'description',
                'start_date',
                'due_date',
                'progress',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create project',
            'updated'  => 'Update project',
            'deleted'  => 'Delete project',
            'restored' => 'Restore project',
            default    => $eventName,
        };
    }
}
