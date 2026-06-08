<?php

namespace App\Repositories;

use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TaskReportRepository
{
    public function getPaginatedReport(array $filters, int $perPage)
    {
        $query = $this->queryWithRelations();
        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAllReport(array $filters)
    {
        $query = $this->queryWithRelations();
        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->get();
    }

    public function getFilterOptions(): array
    {
        $creators = User::whereHas('createdTasks')
            ->with('media')
            ->get(['id', 'name']);

        $statuses = MsTaskStatus::select('id', 'name', 'severity')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();

        return [
            'creators' => $creators,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'types' => $types,
        ];
    }

    protected function queryWithRelations(): Builder
    {
        return Task::with([
            'creator:id,name',
            'creator.media',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title',
        ])->whereHas('project');
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        // CREATOR
        if (! empty($filters['names'])) {
            $names = is_array($filters['names'])
                ? $filters['names']
                : explode(',', $filters['names']);

            $query->whereRelation('users', fn ($query) => $query->whereIn('users.id', $names));
        }

        // STATUS
        if (! empty($filters['statuses'])) {
            $statuses = is_array($filters['statuses'])
                ? $filters['statuses']
                : explode(',', $filters['statuses']);

            $query->whereIn('status_id', $statuses);
        }

        // PRIORITY
        if (! empty($filters['priorities'])) {
            $priorities = is_array($filters['priorities'])
                ? $filters['priorities']
                : explode(',', $filters['priorities']);

            $query->whereIn('priority_id', $priorities);
        }

        // TYPE
        if (! empty($filters['types'])) {
            $types = is_array($filters['types'])
                ? $filters['types']
                : explode(',', $filters['types']);

            $query->whereIn('type_id', $types);
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

        // SEARCH
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }
    }
}
