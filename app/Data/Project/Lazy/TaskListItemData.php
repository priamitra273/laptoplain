<?php

namespace App\Data\Project\Lazy;

use App\Data\Task\Concerns\DerivesTaskCompletion;
use App\Data\Task\TaskCategoryData;
use App\Data\Task\TaskStatusData;
use App\Data\Task\TaskTypeData;
use App\Data\UserData;
use App\Models\Task;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Slim task shape for the List (TreeTable) tab.
 *
 * Only carries the columns the table renders + tree/sort helpers — intentionally
 * omits description, priority, tags, media, creator, story_points and all *_id keys.
 * IDs are emitted as integers and encoded downstream by Sqids::rec_encode_ids_in_list().
 */
class TaskListItemData extends Data
{
    use DerivesTaskCompletion;

    public function __construct(
        public int $id,
        public ?int $parent_id,
        public string $title,
        public float $progress,
        public ?string $start_date,
        public ?string $due_date,
        public ?string $completed_at,
        public bool $is_overdue,
        public ?string $created_at,
        public ?string $updated_at,
        public ?TaskStatusData $status,
        public ?TaskTypeData $type,
        public ?TaskCategoryData $category,
        /** @var DataCollection<int, UserData> */
        public DataCollection $users,
        /** @var DataCollection<int, TaskListItemData> */
        public DataCollection $sub_task_recursive,
    ) {}

    /**
     * @param  DataCollection<int, TaskListItemData>  $children
     */
    public static function fromModel(Task $task, DataCollection $children): self
    {
        [$completedAt, $isOverdue] = self::deriveCompletion($task);

        return new self(
            id: (int) $task->id,
            parent_id: $task->parent_id !== null ? (int) $task->parent_id : null,
            title: (string) $task->title,
            progress: (float) $task->progress,
            start_date: self::asDate($task->start_date),
            due_date: self::asDate($task->due_date),
            completed_at: $completedAt,
            is_overdue: $isOverdue,
            created_at: $task->created_at?->toJSON(),
            updated_at: $task->updated_at?->toJSON(),
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            type: $task->relationLoaded('type') && $task->type ? TaskTypeData::from($task->type) : null,
            category: $task->relationLoaded('category') && $task->category ? TaskCategoryData::from($task->category) : null,
            users: $task->relationLoaded('users')
                ? UserData::collect($task->users, DataCollection::class)
                : UserData::collect([], DataCollection::class),
            sub_task_recursive: $children,
        );
    }

    /**
     * Build a tree from a flat collection of Task models (pure, no DB).
     *
     * @param  SupportCollection<int, Task>  $tasks
     * @return DataCollection<int, TaskListItemData>
     */
    public static function treeFromTasks(SupportCollection $tasks): DataCollection
    {
        $grouped = $tasks->groupBy(fn (Task $t) => (string) ($t->parent_id ?? ''));

        $build = function (Task $task) use (&$build, $grouped): self {
            $childModels = $grouped->get((string) $task->id, collect());
            $childrenData = $childModels->map(fn (Task $child) => $build($child))->values();

            return self::fromModel($task, self::collect($childrenData, DataCollection::class));
        };

        $roots = $grouped->get('', collect())->map(fn (Task $t) => $build($t))->values();

        return self::collect($roots, DataCollection::class);
    }

    private static function asDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
