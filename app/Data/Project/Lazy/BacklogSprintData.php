<?php

namespace App\Data\Project\Lazy;

use App\Models\ProjectSprint;
use App\Models\Task;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Slim sprint shape for the backlog board.
 *
 * Tasks are filtered to non-epic rows (epics never render as sprint rows; they are
 * delivered separately as the `epics` option list). IDs are integers, encoded by Sqids.
 */
class BacklogSprintData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $goal,
        public ?string $duration,
        public ?string $start_date,
        public ?string $end_date,
        public ?int $order,
        public ?SprintStatusData $status,

        /** @var DataCollection<int, BacklogTaskData> */
        public DataCollection $tasks,
    ) {}

    public static function fromModel(ProjectSprint $sprint): self
    {
        $tasks = $sprint->relationLoaded('tasks')
            ? $sprint->tasks
                ->reject(fn (Task $task) => strtolower((string) $task->category?->name) === 'epic')
                ->map(fn (Task $task) => BacklogTaskData::fromModel($task))
                ->values()
            : collect();

        return new self(
            id: (int) $sprint->id,
            name: (string) $sprint->name,
            goal: $sprint->goal,
            duration: $sprint->duration,
            start_date: $sprint->start_date?->format('Y-m-d'),
            end_date: $sprint->end_date?->format('Y-m-d'),
            order: $sprint->order !== null ? (int) $sprint->order : null,
            status: $sprint->relationLoaded('status') && $sprint->status ? SprintStatusData::fromModel($sprint->status) : null,
            tasks: BacklogTaskData::collect($tasks, DataCollection::class),
        );
    }
}
