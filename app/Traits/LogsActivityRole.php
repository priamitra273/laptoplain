<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityRole
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('role')
            ->logOnly([
                'name',
                'label',
                'guard_name',
                'is_active',
                'team_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created'  => 'Create role',
            'updated'  => 'Update role',
            'deleted'  => 'Delete role',
            default    => $eventName,
        };
    }
}
