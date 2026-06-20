<?php

namespace App\Data\Task\Concerns;

use App\Models\Task;
use Carbon\Carbon;

trait DerivesTaskCompletion
{
    private const COMPLETED_STATUSES = ['COMPLETED', 'FINISHED'];

    /**
     * Derive [completed_at, is_overdue] for a task, matching the parity rules in
     * App\Data\Task\ProjectTaskData::fromModel():
     * - completed_at is derived from updated_at (not the DB column) when the status is completed.
     * - is_overdue: a null due_date resolves to "now" via Carbon::parse(null), i.e. not overdue.
     *
     * @return array{0: ?string, 1: bool}
     */
    protected static function deriveCompletion(Task $task): array
    {
        $statusName = $task->relationLoaded('status') ? ($task->status?->name ?? '') : '';

        $isCompleted = in_array(strtoupper($statusName), self::COMPLETED_STATUSES, true);

        $completedAt = $isCompleted ? $task->updated_at?->toJSON() : null;

        $isOverdue = $isCompleted
            ? Carbon::parse($task->updated_at)->isAfter(Carbon::parse($task->due_date)->endOfDay())
            : Carbon::parse($task->due_date)->endOfDay()->isPast();

        return [$completedAt, $isOverdue];
    }
}
