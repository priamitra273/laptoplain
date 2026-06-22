<?php

namespace App\Data\Task;

use App\Data\Project\ProjectOptionData;
use App\Data\Task\Concerns\DerivesTaskCompletion;
use App\Data\UserData;
use App\Models\Task;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

/**
 * Slim task shape for the "My Task" index (board + flat list).
 *
 * Intentionally omits the recursive subtask tree: the view only needs aggregate
 * subtask counts. Heavier fields (description, tags, media, creator, story_points,
 * progress) are dropped because neither the board card nor the list column renders them.
 */
#[TypeScript]
class AssignedTaskData extends Data
{
    use DerivesTaskCompletion;

    public function __construct(
        #[TypeScriptType('string')] public int $id,
        public string $title,
        public ?string $due_date,
        public bool $is_overdue,
        public ?int $sequence_number,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskTypeData $type,
        public ?ProjectOptionData $project,
        /** @var DataCollection<int, UserData> */
        #[TypeScriptType('Array<UserData>')]
        public DataCollection $users,
        public int $sub_task_count,
        public int $sub_task_done_count,
    ) {}

    public static function fromModel(Task $task): self
    {
        [, $isOverdue] = self::deriveCompletion($task);

        return new self(
            id: (int) $task->id,
            title: (string) $task->title,
            due_date: self::asDate($task->due_date),
            is_overdue: $isOverdue,
            sequence_number: $task->sequence_number !== null ? (int) $task->sequence_number : null,
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            priority: $task->relationLoaded('priority') && $task->priority ? TaskPriorityData::from($task->priority) : null,
            type: $task->relationLoaded('type') && $task->type ? TaskTypeData::from($task->type) : null,
            project: $task->relationLoaded('project') && $task->project ? ProjectOptionData::fromModel($task->project) : null,
            users: $task->relationLoaded('users')
                ? UserData::collect($task->users, DataCollection::class)
                : UserData::collect([], DataCollection::class),
            sub_task_count: (int) ($task->sub_task_count ?? 0),
            sub_task_done_count: (int) ($task->sub_task_done_count ?? 0),
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
