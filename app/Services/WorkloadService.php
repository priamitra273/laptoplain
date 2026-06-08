<?php

namespace App\Services;

use App\Data\Workload\WorkloadFiltersData;
use App\Data\Workload\WorkloadOptionsData;
use App\Data\Workload\WorkloadSummaryData;
use App\Data\Workload\WorkloadUserData;
use App\Enums\WorkloadStatus;
use App\Facades\Sqids;
use App\Repositories\WorkloadRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkloadService
{
    public function __construct(
        protected WorkloadRepository $repository
    ) {}

    public function getUsersWorkload(WorkloadFiltersData $filters): LengthAwarePaginator
    {
        $query = $this->repository->getWorkloadBaseQuery();
        $this->repository->applyFilters($query, $filters);

        return $query
            ->orderByDesc('w.remaining_work_percent')
            ->paginate($filters->per_page)
            ->withQueryString()
            ->through(fn ($user) => WorkloadUserData::fromResource($user));
    }

    public function getWorkloadSummary(WorkloadFiltersData $filters): WorkloadSummaryData
    {
        $query = $this->repository->getWorkloadBaseQuery();
        $this->repository->applyFilters($query, $filters);

        $summary = $this->repository->getSummary($query);

        return new WorkloadSummaryData(...$summary);
    }

    public function getFilterOptions(): WorkloadOptionsData
    {
        $users = $this->repository->getAvailableUsers()
            ->map(fn ($user) => [
                'id' => Sqids::encode($user->id),
                'name' => $user->name,
                'avatar_url' => $user->avatar_url ?? null,
            ])
            ->toArray();

        $workloadStatuses = collect(WorkloadStatus::cases())
            ->map(fn (WorkloadStatus $status) => [
                'id' => $status->id(),
                'name' => $status->label(),
                'severity' => $status->severity()->value,
            ])
            ->toArray();

        return new WorkloadOptionsData(
            users: $users,
            workload_statuses: $workloadStatuses,
        );
    }
}
