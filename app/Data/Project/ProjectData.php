<?php

namespace App\Data\Project;

use App\Data\UserData;
use App\Models\Project;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectData extends Data
{
    public function __construct(
        public int $id,
        public ?string $project_no,
        public ?string $title,
        public ?string $description,
        public ?string $emoji,
        public ?string $start_date,
        public ?string $due_date,
        public float $progress,
        public ?int $status_id,
        public ?int $priority_id,
        public ?int $owner_id,
        public ?int $owned_id,
        public ?int $sequence_number,
        public ?string $created_at,
        public ?string $updated_at,
        public ?ProjectStatusData $status,
        public ?ProjectPriorityData $priority,
        public ?UserData $owner,
        public ?UserData $owned,
    ) {}

    public static function fromModel(Project $project): self
    {
        return new self(
            $project->id,
            $project->project_no,
            $project->title,
            $project->description,
            $project->emoji,
            $project->start_date?->format('Y-m-d'),
            $project->due_date?->format('Y-m-d'),
            (float) ($project->progress ?? 0),
            $project->status_id,
            $project->priority_id,
            $project->owner_id,
            $project->owned_id,
            $project->sequence_number,
            $project->created_at?->toJSON(),
            $project->updated_at?->toJSON(),
            $project->relationLoaded('status') && $project->status ? ProjectStatusData::from($project->status) : null,
            $project->relationLoaded('priority') && $project->priority ? ProjectPriorityData::from($project->priority) : null,
            $project->relationLoaded('owner') && $project->owner ? UserData::from($project->owner) : null,
            $project->relationLoaded('owned') && $project->owned ? UserData::from($project->owned) : null,
        );
    }
}
