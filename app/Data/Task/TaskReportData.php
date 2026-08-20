<?php

namespace App\Data\Task;

use App\Facades\Sqids;
use App\Models\Task;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskReportData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $summary,
        public ?array $creator,
        public ?array $status,
        public ?array $priority,
        public ?array $type,
        public ?array $project,
        public ?string $start_date,
        public ?string $due_date,
        public ?float $progress,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Task $task): self
    {
        return new self(
            id: Sqids::encode($task->id),
            title: $task->title,
            description: $task->description,
            summary: self::generateSummary($task->description),
            creator: $task->creator ? [
                'id' => Sqids::encode($task->creator->id),
                'name' => $task->creator->name,
                'avatar_url' => $task->creator->avatar_url,
            ] : null,
            status: $task->status ? [
                'id' => Sqids::encode($task->status->id),
                'name' => $task->status->name,
                'severity' => $task->status->severity,
            ] : null,
            priority: $task->priority ? [
                'id' => Sqids::encode($task->priority->id),
                'name' => $task->priority->name,
                'severity' => $task->priority->severity,
            ] : null,
            type: $task->type ? [
                'id' => Sqids::encode($task->type->id),
                'name' => $task->type->name,
                'severity' => $task->type->severity,
            ] : null,
            project: $task->project ? [
                'id' => Sqids::encode($task->project->id),
                'title' => $task->project->title,
                'status' => $task->project->status ? [
                    'id' => Sqids::encode($task->project->status->id),
                    'name' => $task->project->status->name,
                    'severity' => $task->project->status->severity,
                ] : null,
            ] : null,
            start_date: $task->start_date instanceof Carbon ? $task->start_date->toDateString() : $task->start_date,
            due_date: $task->due_date instanceof Carbon ? $task->due_date->toDateString() : $task->due_date,
            progress: (float) $task->progress,
            created_at: $task->created_at->toJSON(),
            updated_at: $task->updated_at->toJSON(),
        );
    }

    private static function generateSummary(?string $description): string
    {
        if (! $description) {
            return '-';
        }

        $text = strip_tags($description);

        if (strlen($text) > 100) {
            return substr($text, 0, 100).'...';
        }

        return $text;
    }
}
