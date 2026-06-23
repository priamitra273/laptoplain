<?php

namespace App\Data\Task;

use App\Data\MediaData;
use App\Data\UserData;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class ProjectTaskData extends Data
{
    private const COMPLETED_STATUSES = ['COMPLETED', 'FINISHED'];

    public function __construct(
        #[TypeScriptType('string')] public int $id,
        #[TypeScriptType('string|null')] public ?int $owned_id,
        #[TypeScriptType('string|null')] public ?int $parent_id,
        #[TypeScriptType('string|null')] public ?int $status_id,
        #[TypeScriptType('string|null')] public ?int $priority_id,
        #[TypeScriptType('string|null')] public ?int $type_id,
        public ?int $created_by,
        public ?int $updated_by,
        public ?int $deleted_by,
        public ?string $emoji,
        public string $title,
        public ?string $description,
        public ?string $start_date,
        public ?string $due_date,
        public float $progress,
        public ?int $story_points,
        public ?int $sequence_number,
        public bool $is_archived,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at,
        public ?string $completed_at,
        public bool $is_overdue,
        #[TypeScriptType('string')] public int $project_id,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskTypeData $type,
        public ?TaskCategoryData $category,
        public ?UserData $creator,
        /** @var DataCollection<int, UserData> */
        #[TypeScriptType('Array<UserData>')]
        public DataCollection $users,
        /** @var DataCollection<int, TagData> */
        #[TypeScriptType('Array<TagData>')]
        public DataCollection $tags,
        /** @var DataCollection<int, MediaData> */
        #[TypeScriptType('Array<MediaData>')]
        public DataCollection $media,
        /** @var DataCollection<int, ProjectTaskData> */
        #[TypeScriptType('Array<ProjectTaskData>')]
        public DataCollection $sub_task,
        /** @var DataCollection<int, ProjectTaskData> */
        #[TypeScriptType('Array<ProjectTaskData>')]
        public DataCollection $sub_task_recursive,
        // Intentionally null here; populated by the service layer when needed.
        public ?bool $is_assigned = null,
        public ?bool $is_created_by_me = null,
    ) {}

    /**
     * Build a ProjectTaskData from a Task model.
     *
     * Parity notes (must match legacy ProjectService::formatProjectTasks()):
     * - `completed_at` is derived from `updated_at`, not the DB `completed_at` column.
     * - `is_overdue`: when `due_date` is null, Carbon::parse(null) resolves to now,
     *   so the task is treated as not overdue — this matches the old behavior.
     */
    public static function fromModel(Task $task, DataCollection $children): self
    {
        $statusName = $task->relationLoaded('status') ? ($task->status?->name ?? '') : '';

        $isCompleted = in_array(
            strtoupper($statusName),
            self::COMPLETED_STATUSES,
            true
        );

        $completedAt = $isCompleted ? $task->updated_at?->toJSON() : null;

        $isOverdue = $isCompleted
            ? Carbon::parse($task->updated_at)->isAfter(Carbon::parse($task->due_date)->endOfDay())
            : Carbon::parse($task->due_date)->endOfDay()->isPast();

        return new self(
            id: (int) $task->id,
            owned_id: $task->owned_id !== null ? (int) $task->owned_id : null,
            parent_id: $task->parent_id !== null ? (int) $task->parent_id : null,
            status_id: $task->status_id !== null ? (int) $task->status_id : null,
            priority_id: $task->priority_id !== null ? (int) $task->priority_id : null,
            type_id: $task->type_id !== null ? (int) $task->type_id : null,
            created_by: is_numeric($task->created_by) ? (int) $task->created_by : null,
            updated_by: is_numeric($task->updated_by) ? (int) $task->updated_by : null,
            deleted_by: is_numeric($task->deleted_by) ? (int) $task->deleted_by : null,
            emoji: $task->emoji,
            title: (string) $task->title,
            description: $task->description,
            start_date: self::asString($task->start_date),
            due_date: self::asString($task->due_date),
            progress: (float) $task->progress,
            story_points: $task->story_points !== null ? (int) $task->story_points : null,
            sequence_number: $task->sequence_number !== null ? (int) $task->sequence_number : null,
            is_archived: (bool) $task->is_archived,
            created_at: $task->created_at?->toJSON(),
            updated_at: $task->updated_at?->toJSON(),
            deleted_at: $task->deleted_at?->toJSON(),
            completed_at: $completedAt,
            is_overdue: $isOverdue,
            project_id: (int) $task->project_id,
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            priority: $task->relationLoaded('priority') && $task->priority ? TaskPriorityData::from($task->priority) : null,
            type: $task->relationLoaded('type') && $task->type ? TaskTypeData::from($task->type) : null,
            category: $task->relationLoaded('category') && $task->category ? TaskCategoryData::from($task->category) : null,
            creator: $task->relationLoaded('creator') && $task->creator ? UserData::fromModel($task->creator) : null,
            users: $task->relationLoaded('users')
                ? UserData::collect($task->users, DataCollection::class)
                : UserData::collect([], DataCollection::class),
            tags: $task->relationLoaded('tags')
                ? TagData::collect($task->tags, DataCollection::class)
                : TagData::collect([], DataCollection::class),
            media: $task->relationLoaded('media')
                ? MediaData::collect($task->media, DataCollection::class)
                : MediaData::collect([], DataCollection::class),
            // sub_task and sub_task_recursive intentionally share the same children collection (recursive tree).
            sub_task: $children,
            sub_task_recursive: $children,
        );
    }

    /**
     * Build a tree of ProjectTaskData from a flat collection of Task models.
     * Pure (no DB). Input order is preserved within each parent group.
     *
     * @param  SupportCollection<int, Task>  $tasks
     * @return DataCollection<int, ProjectTaskData>
     */
    public static function treeFromTasks(SupportCollection $tasks): DataCollection
    {
        $grouped = $tasks->groupBy(fn (Task $t) => (string) ($t->parent_id ?? ''));

        $build = function (Task $task) use (&$build, $grouped): ProjectTaskData {
            $childModels = $grouped->get((string) $task->id, collect());
            $childrenData = $childModels->map(fn (Task $child) => $build($child))->values();

            return self::fromModel($task, self::collect($childrenData, DataCollection::class));
        };

        $roots = $grouped->get('', collect())->map(fn (Task $t) => $build($t))->values();

        return self::collect($roots, DataCollection::class);
    }

    private static function asString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
