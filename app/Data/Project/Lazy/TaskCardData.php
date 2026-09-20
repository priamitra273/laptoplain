<?php

namespace App\Data\Project\Lazy;

use App\Data\Task\Concerns\DerivesTaskCompletion;
use App\Data\Task\TaskPriorityData;
use App\Data\Task\TaskStatusData;
use App\Data\Task\TaskTypeData;
use App\Data\UserData;
use App\Models\Task;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Slim task shape for the Kanban board card + detail slide-over.
 *
 * sub_task_recursive is kept (recursively) so the board can compute subtask totals
 * and done-counts client-side. IDs are integers, encoded downstream by Sqids.
 */
class TaskCardData extends Data
{
    use DerivesTaskCompletion;

    public function __construct(
        public int $id,
        public string $code,
        public int $comments_count,
        public ?int $parent_id,
        public string $title,
        public ?string $description,
        public ?string $start_date,
        public ?string $due_date,
        public float $progress,
        public bool $is_overdue,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskTypeData $type,

        /** @var DataCollection<int, UserData> */
        public DataCollection $users,

        /** @var DataCollection<int, TaskCardData> */
        public DataCollection $sub_task_recursive,
    ) {}

    public static function fromModel(Task $task): self
    {
        [, $isOverdue] = self::deriveCompletion($task);

        $children = $task->relationLoaded('subTaskRecursive')
            ? self::collect($task->subTaskRecursive->map(fn (Task $child) => self::fromModel($child)), DataCollection::class)
            : self::collect([], DataCollection::class);

        return new self(
            id: (int) $task->id,
            code: 'T-'.$task->id,
            comments_count: (int) ($task->comments_count ?? 0),
            parent_id: $task->parent_id !== null ? (int) $task->parent_id : null,
            title: (string) $task->title,
            description: $task->description,
            start_date: self::asDate($task->start_date),
            due_date: self::asDate($task->due_date),
            progress: (float) $task->progress,
            is_overdue: $isOverdue,
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            priority: $task->relationLoaded('priority') && $task->priority ? TaskPriorityData::from($task->priority) : null,
            type: $task->relationLoaded('type') && $task->type ? TaskTypeData::from($task->type) : null,
            users: $task->relationLoaded('users')
                ? UserData::collect($task->users, DataCollection::class)
                : [],
            sub_task_recursive: $children,
        );
    }

    private static function asDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
