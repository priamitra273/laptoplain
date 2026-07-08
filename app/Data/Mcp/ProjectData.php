<?php

namespace App\Data\Mcp;

use App\Facades\Sqids;
use App\Models\Project;
use Spatie\LaravelData\Data;

class ProjectData extends Data
{
    public function __construct(
        public string $id,
        public string $project_no,

        public string $emoji,
        public string $title,
        public string $status_id,
        public string $status_name,
        public string $priority_id,
        public string $priority_name,
        public string $description,
        public string $start_date,
        public string $due_date,
        public float $progress,
    ) {}

    public static function fromModel(Project $project): static
    {
        return new static(
            id: Sqids::encode($project->id),
            project_no: $project->project_no,
            emoji: $project->emoji,
            title: $project->title,
            status_id: Sqids::encode($project->status_id),
            status_name: $project->status->name,
            priority_id: Sqids::encode($project->priority_id),
            priority_name: $project->priority->name,
            description: $project->description,
            start_date: $project->start_date->format('Y-m-d'),
            due_date: $project->due_date->format('Y-m-d'),
            progress: $project->progress,
        );
    }
}
