<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\TaskReport\TaskReportIndexRequest;
use App\Models\MsProjectStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskReportController extends Controller
{
    /**
     * Display task report with filters
     */
    public function index(TaskReportIndexRequest $request)
    {
        // Get filter parameters
        $filters = $request->validated();

        $query = Task::with([
            'creator:id,name',
            'creator.media',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title,status_id',
            'project.status:id,name,severity',
        ])
            ->whereHas('project', function ($query) use ($filters) {
                if (! empty($filters['project_statuses'])) {
                    $projectStatuses = is_array($filters['project_statuses'])
                        ? $filters['project_statuses']
                        : explode(',', $filters['project_statuses']);

                    $projectStatusIds = array_map(fn ($encoded) => Sqids::decode($encoded), $projectStatuses);

                    $query->whereIn('projects.status_id', $projectStatusIds);
                }
            });

        $this->applyFilters($query, $filters);

        $perPage = $request->get('per_page', 25);
        $tasks = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $formattedTasks = $tasks->through(function ($task) {
            return [
                'id' => Sqids::encode($task->id), // Encode task ID
                'title' => $task->title,
                'description' => $task->description,
                'summary' => $this->generateSummary($task->description),
                'creator' => $task->creator ? [
                    'id' => Sqids::encode($task->creator->id), // Encode creator ID
                    'name' => $task->creator->name,
                    'avatar_url' => $task->creator->avatar_url ?? null,
                ] : null,
                'status' => $task->status ? [
                    'id' => Sqids::encode($task->status->id), // Encode status ID
                    'name' => $task->status->name,
                    'severity' => $task->status->severity,
                ] : null,
                'priority' => $task->priority ? [
                    'id' => Sqids::encode($task->priority->id), // Encode priority ID
                    'name' => $task->priority->name,
                    'severity' => $task->priority->severity,
                ] : null,
                'type' => $task->type ? [
                    'id' => Sqids::encode($task->type->id), // Encode type ID
                    'name' => $task->type->name,
                    'severity' => $task->type->severity,
                ] : null,
                'project' => $task->project ? [
                    'id' => Sqids::encode($task->project->id), // Encode project ID
                    'title' => $task->project->title,
                    'status_name' => $task->project->status->name,
                    'severity' => $task->project->status->severity,
                ] : null,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'progress' => $task->progress,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ];
        });

        $filterOptions = $this->getFilterOptions();

        $response = [
            'tasks' => $formattedTasks,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ];

        // Tidak perlu Sqids::rec_encode_ids_in_list karena sudah manual encode di atas
        return Inertia::render('project/task/TaskReport', $response);
    }

    public function export(Request $request)
    {
        $filters = $request->only([
            'names',
            'statuses',
            'priorities',
            'types',
            'start_date_from',
            'start_date_to',
            'due_date_from',
            'due_date_to',
            'search',
        ]);

        $query = Task::with([
            'creator:id,name',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title',
        ]);

        $this->applyFilters($query, $filters);

        $tasks = $query->orderByDesc('created_at')->get();

        $filename = 'task-report-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($tasks) {
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
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $this->generateSummary($task->description),
                    $task->creator?->name ?? '-',
                    $task->status?->name ?? '-',
                    $task->priority?->name ?? '-',
                    $task->type?->name ?? '-',
                    $task->project?->title ?? '-',
                    $task->start_date ?? '-',
                    $task->due_date ?? '-',
                    $task->progress ?? 0,
                    $task->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function applyFilters($query, array $filters)
    {
        // CREATOR
        if (! empty($filters['names'])) {
            $names = is_array($filters['names'])
                ? $filters['names']
                : explode(',', $filters['names']);

            $creatorIds = array_map(fn ($encoded) => Sqids::decode($encoded), $names);

            $query->whereRelation('users', fn ($query) => $query->whereIn('users.id', $creatorIds));
        }

        // STATUS
        if (! empty($filters['statuses'])) {
            $statuses = is_array($filters['statuses'])
                ? $filters['statuses']
                : explode(',', $filters['statuses']);

            $statusIds = array_map(fn ($encoded) => Sqids::decode($encoded), $statuses);

            $query->whereIn('status_id', $statusIds);
        }

        // PRIORITY
        if (! empty($filters['priorities'])) {
            $priorities = is_array($filters['priorities'])
                ? $filters['priorities']
                : explode(',', $filters['priorities']);

            $priorityIds = array_map(fn ($encoded) => Sqids::decode($encoded), $priorities);

            $query->whereIn('priority_id', $priorityIds);
        }

        // TYPE
        if (! empty($filters['types'])) {
            $types = is_array($filters['types'])
                ? $filters['types']
                : explode(',', $filters['types']);

            $typeIds = array_map(fn ($encoded) => Sqids::decode($encoded), $types);

            $query->whereIn('type_id', $typeIds);
        }

        // DATE FILTERS
        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }

        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        // SEARCH (PostgreSQL friendly)
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }

    private function getFilterOptions()
    {
        $projectStatuses = MsProjectStatus::select('id', 'name', 'severity')
            ->get()
            ->map(function ($projectStatus) {
                return [
                    'id' => Sqids::encode($projectStatus->id), // Encode project status ID untuk filter
                    'name' => $projectStatus->name,
                    'severity' => $projectStatus->severity,
                ];
            });

        $creators = User::whereHas('createdTasks')
            ->with('media')
            ->get(['id', 'name'])
            ->map(function ($user) {
                return [
                    'id' => Sqids::encode($user->id), // Encode creator ID untuk filter
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url ?? null,
                ];
            });

        $statuses = MsTaskStatus::select('id', 'name', 'severity')
            ->get()
            ->map(function ($status) {
                return [
                    'id' => Sqids::encode($status->id), // Encode status ID untuk filter
                    'name' => $status->name,
                    'severity' => $status->severity,
                ];
            });

        $priorities = MsTaskPriority::select('id', 'name', 'severity')
            ->get()
            ->map(function ($priority) {
                return [
                    'id' => Sqids::encode($priority->id), // Encode priority ID untuk filter
                    'name' => $priority->name,
                    'severity' => $priority->severity,
                ];
            });

        $types = MsTaskType::select('id', 'name', 'severity')
            ->get()
            ->map(function ($type) {
                return [
                    'id' => Sqids::encode($type->id), // Encode type ID untuk filter
                    'name' => $type->name,
                    'severity' => $type->severity,
                ];
            });

        return [
            'project_statuses' => $projectStatuses->toArray(),
            'creators' => $creators->toArray(),
            'statuses' => $statuses->toArray(),
            'priorities' => $priorities->toArray(),
            'types' => $types->toArray(),
        ];
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
