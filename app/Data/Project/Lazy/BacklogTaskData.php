<?php

namespace App\Data\Project\Lazy;

use App\Data\Task\TaskCategoryData;
use App\Data\Task\TaskPriorityData;
use App\Data\Task\TaskStatusData;
use App\Data\UserData;
use App\Models\Task;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Slim task shape for the backlog board rows (sprint lists + backlog list).
 *
 * Carries only what a row renders — title, story points, category, priority, status
 * and assignees. Intentionally omits description, type, dates, progress, tags, media,
 * sub-tasks and all *_id keys. Epic rows are filtered out upstream (epics are delivered
 * separately as the `epics` option list). IDs are integers, encoded downstream by Sqids.
 */
class BacklogTaskData extends Data
{
    public function __construct(
        public int $id,
        public ?int $parent_id,
        public string $title,
        public ?int $story_points,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskCategoryData $category,

        /** @var DataCollection<int, UserData> */
        public DataCollection $users,
    ) {}

    public static function fromModel(Task $task): self
    {
        return new self(
            id: (int) $task->id,
            parent_id: $task->parent_id !== null ? (int) $task->parent_id : null,
            title: (string) $task->title,
            story_points: $task->story_points !== null ? (int) $task->story_points : null,
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            priority: $task->relationLoaded('priority') && $task->priority ? TaskPriorityData::from($task->priority) : null,
            category: $task->relationLoaded('category') && $task->category ? TaskCategoryData::from($task->category) : null,
            users: $task->relationLoaded('users')
                ? UserData::collect($task->users, DataCollection::class)
                : UserData::collect([], DataCollection::class),
        );
    }
}
