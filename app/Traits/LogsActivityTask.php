<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
            'created' => 'Create task',
            'updated' => 'Update task',
            'deleted' => 'Delete task',
            'restored' => 'Restore task',
            default => $eventName,
        };
    }

    protected static function activityFieldLabels(): array
    {
        return [
            'project_id' => 'Project',
            'parent_id' => 'Parent Task',
            'status_id' => 'Status',
            'priority_id' => 'Priority',
            'type_id' => 'Type',
            'owned_id' => 'Owner',
            'title' => 'Title',
            'description' => 'Description',
            'start_date' => 'Start Date',
            'due_date' => 'Due Date',
            'progress' => 'Progress',
            'is_archived' => 'Archived',
        ];
    }

    /**
     * Kembalikan daftar nama field yang berubah (tanpa value).
     */
    public static function getFormattedActivities(int $taskId, ?string $event = null): \Illuminate\Support\Collection
    {
        $labels = static::activityFieldLabels();

        $query = \Spatie\Activitylog\Models\Activity::query()
            ->with('causer:id,name')
            ->where('subject_type', static::class)
            ->where('subject_id', $taskId)
            ->orderByDesc('created_at');

        if ($event !== null) {
            $query->where('event', $event);
        }

        return $query->get()->map(function ($activity) use ($labels) {
            $old = $activity->properties['old'] ?? [];
            $attributes = $activity->properties['attributes'] ?? [];

            // Kumpulkan perubahan field:
            // - status_id → sertakan old & new value (nama status)
            // - field lain → hanya nama field saja
            $changedFields = [];
            foreach ($attributes as $field => $newRaw) {
                $oldRaw = $old[$field] ?? null;
                if ($oldRaw === $newRaw) {
                    continue;
                }

                if ($field === 'status_id') {
                    $changedFields[] = [
                        'field' => $labels[$field] ?? $field,
                        'old_value' => $oldRaw
                            ? (\App\Models\MsTaskStatus::find($oldRaw)?->name ?? (string) $oldRaw)
                            : null,
                        'new_value' => $newRaw
                            ? (\App\Models\MsTaskStatus::find($newRaw)?->name ?? (string) $newRaw)
                            : null,
                        'has_value' => true,
                    ];
                } else {
                    $changedFields[] = [
                        'field' => $labels[$field] ?? $field,
                        'old_value' => null,
                        'new_value' => null,
                        'has_value' => false,
                    ];
                }
            }

            $causer = null;
            if ($activity->causer) {
                $causer = [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'avatar_url' => method_exists($activity->causer, 'getFirstMediaUrl')
                        ? ($activity->causer->getFirstMediaUrl('avatars') ?: null)
                        : ($activity->causer->avatar_url ?? null),
                ];
            }

            return [
                'id' => $activity->id,
                'event' => $activity->event ?? 'updated',
                'causer' => $causer,
                'changed_fields' => $changedFields,
                'created_at' => $activity->created_at->toIso8601String(),
            ];
        });
    }
}
