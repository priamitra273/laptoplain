<?php

namespace App\Services;

use App\Data\Project\ProjectStatusData;
use App\Data\Task\FilterOptionData;
use App\Data\Task\TaskReportData;
use App\Data\Task\TaskReportIndexData;
use App\Facades\Sqids;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskReportRepository;
use Closure;
use Spatie\LaravelData\DataCollection;

class TaskReportService
{
    public function __construct(
        protected TaskReportRepository $repository,
        protected ProjectRepository $projectRepository
    ) {}

    public function getIndexData(array $filters, int $perPage): TaskReportIndexData
    {
        $paginatedTasks = $this->repository->getPaginatedReport($filters, $perPage);
        $tasks = TaskReportData::collect($paginatedTasks);

        $filterOptions = $this->repository->getFilterOptions();

        $project_statuses = ProjectStatusData::collect(
            $this->projectRepository->getProjectStatuses(),
            DataCollection::class
        );

        $formattedOptions = [
            'creators' => FilterOptionData::collect($filterOptions['creators']->map(fn ($user) => [
                'id' => Sqids::encode($user->id),
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ])),

            'statuses' => FilterOptionData::collect($filterOptions['statuses']->map(fn ($status) => [
                'id' => Sqids::encode($status->id),
                'name' => $status->name,
                'severity' => $status->severity,
            ])),

            'priorities' => FilterOptionData::collect($filterOptions['priorities']->map(fn ($priority) => [
                'id' => Sqids::encode($priority->id),
                'name' => $priority->name,
                'severity' => $priority->severity,
            ])),

            'types' => FilterOptionData::collect($filterOptions['types']->map(fn ($type) => [
                'id' => Sqids::encode($type->id),
                'name' => $type->name,
                'severity' => $type->severity,
            ])),

            'project_statuses' => Sqids::rec_encode_ids_in_list($project_statuses->toArray()),
        ];

        return new TaskReportIndexData(
            tasks: $tasks,
            filters: array_map(fn ($item) => array_map(fn ($value) => Sqids::encode($value), $item), $filters),
            filterOptions: $formattedOptions,
            project_statuses: Sqids::rec_encode_ids_in_list($project_statuses->toArray()),
        );
    }

    public function generateExportCallback(array $filters): Closure
    {
        $tasks = $this->repository->getAllReport($filters);

        return function () use ($tasks) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Task ID',
                'Title',
                'Summary',
                'Creator',
                'Status',
                'Priority',
                'Type',
                'Project',
                'Start Date',
                'Due Date',
                'Progress (%)',
                'Created At',
            ]);

            // CSV Data
            foreach ($tasks as $task) {
                // We don't use TaskReportData here to avoid unnecessary Overhead if many rows
                // but we use the helper logic
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $this->generateSummary($task->description),
                    $task->creator?->name ?? '-',
                    $task->status?->name ?? '-',
                    $task->priority?->name ?? '-',
                    $task->type?->name ?? '-',
                    $task->project?->title ?? '-',
                    $task->start_date instanceof \Carbon\Carbon ? $task->start_date->toDateString() : ($task->start_date ?? '-'),
                    $task->due_date instanceof \Carbon\Carbon ? $task->due_date->toDateString() : ($task->due_date ?? '-'),
                    $task->progress ?? 0,
                    $task->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };
    }

    private function generateSummary(?string $description): string
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
