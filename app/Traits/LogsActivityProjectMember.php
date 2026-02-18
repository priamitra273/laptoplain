<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityProjectMember
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project-member')
            ->logOnly([
                'project_id',
                'user_id',
                'project_role_id',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create project member',
            'updated'  => 'Update data project member',
            'deleted'  => 'Delete project member',
            'restored' => 'Restore project member',
            default    => $eventName,
        };
    }
}
