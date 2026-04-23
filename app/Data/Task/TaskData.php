<?php

namespace App\Data\Task;

use App\Data\UserData;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class TaskData extends Data
{
    public function __construct(
        #[TypeScriptType('string')]
        public int $id,
        public string $title,
        public ?string $description,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskCategoryData $category,

        #[TypeScriptType('Array<UserData>|null')]
        /** @var DataCollection<int, UserData> */
        public DataCollection|Collection|Optional|null $users,

        public ?float $progress,
        public ?int $story_points,
    ) {}

    public static function fromModel(Task $task): self
    {
        return new self(
            id: $task->id,
            title: $task->title,
            description: $task->description,
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            priority: $task->relationLoaded('priority') && $task->priority ? TaskPriorityData::from($task->priority) : null,
            category: $task->relationLoaded('category') && $task->category ? TaskCategoryData::from($task->category) : null,
            users: $task->relationLoaded('users') ? UserData::collect($task->users) : null,
            progress: (float) $task->progress,
            story_points: $task->story_points,
        );
    }
}
