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

    /**
     * Human-readable field labels
     */
    protected static function activityFieldLabels(): array
    {
        return [
            'project_id'  => 'Project',
            'parent_id'   => 'Parent Task',
            'status_id'   => 'Status',
            'priority_id' => 'Priority',
            'type_id'     => 'Type',
            'owned_id'    => 'Owner',
            'title'       => 'Title',
            'description' => 'Description',
            'start_date'  => 'Start Date',
            'due_date'    => 'Due Date',
            'progress'    => 'Progress',
            'is_archived' => 'Archived',
        ];
    }

    /**
     * Resolve a raw DB value to a human-readable string.
     */
    protected static function resolveActivityValue(string $field, mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return match ($field) {
            'status_id'   => \App\Models\MsTaskStatus::find($value)?->name   ?? (string) $value,
            'priority_id' => \App\Models\MsTaskPriority::find($value)?->name ?? (string) $value,
            'type_id'     => \App\Models\MsTaskType::find($value)?->name     ?? (string) $value,
            'owned_id'    => \App\Models\User::find($value)?->name           ?? (string) $value,
            'project_id'  => \App\Models\Project::find($value)?->title       ?? (string) $value,
            'parent_id'   => \App\Models\Task::withTrashed()->find($value)?->title ?? (string) $value,
            'is_archived' => $value ? 'Yes' : 'No',
            'progress'    => $value . '%',
            default       => (string) $value,
        };
    }

    /**
     * Return formatted activity log entries for a given task ID.
     *
     * Each entry:
     * [
     *   'id'          => int,
     *   'event'       => string,         // created | updated | deleted | restored
     *   'description' => string,
     *   'causer'      => ['id', 'name', 'avatar_url'] | null,
     *   'changes'     => [['field', 'old_value', 'new_value'], ...],
     *   'created_at'  => ISO-8601 string,
     * ]
     */
    public static function getFormattedActivities(int $taskId): \Illuminate\Support\Collection
    {
        $labels = static::activityFieldLabels();

        return \Spatie\Activitylog\Models\Activity::query()
            ->with('causer:id,name')
            ->where('subject_type', static::class)
            ->where('subject_id', $taskId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($activity) use ($labels) {
                $old        = $activity->properties['old']        ?? [];
                $attributes = $activity->properties['attributes'] ?? [];

                // Build a diff only for fields that actually changed
                $changes = [];
                foreach ($attributes as $field => $newRaw) {
                    $oldRaw = $old[$field] ?? null;

                    // Skip if identical (guard against spatie edge-cases)
                    if ($oldRaw === $newRaw) {
                        continue;
                    }

                    $changes[] = [
                        'field'     => $labels[$field] ?? $field,
                        'old_value' => static::resolveActivityValue($field, $oldRaw),
                        'new_value' => static::resolveActivityValue($field, $newRaw),
                    ];
                }

                // Resolve causer with avatar (media library compatible)
                $causer = null;
                if ($activity->causer) {
                    $causer = [
                        'id'         => $activity->causer->id,
                        'name'       => $activity->causer->name,
                        'avatar_url' => method_exists($activity->causer, 'getFirstMediaUrl')
                            ? ($activity->causer->getFirstMediaUrl('avatars') ?: null)
                            : ($activity->causer->avatar_url ?? null),
                    ];
                }

                return [
                    'id'          => $activity->id,
                    'event'       => $activity->event ?? 'updated',
                    'description' => $activity->description,
                    'causer'      => $causer,
                    'changes'     => $changes,
                    'created_at'  => $activity->created_at->toIso8601String(),
                ];
            });
    }
}
