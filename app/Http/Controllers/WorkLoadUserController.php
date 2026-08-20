<?php

namespace App\Http\Controllers;

use App\Data\Workload\WorkloadFiltersData;
use App\Services\WorkloadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkLoadUserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WorkloadService $workloadService
    ) {}

    /**
     * Display a listing of the workload for all users.
     */
    public function index(Request $request): Response
    {
        $filters = WorkloadFiltersData::fromRequest($request);

        return Inertia::render('favorites/workload/Index', [
            'users' => $this->workloadService->getUsersWorkload($filters),
            'summary' => $this->workloadService->getWorkloadSummary($filters),
            'filters' => $filters,
            'filterOptions' => $this->workloadService->getFilterOptions(),
        ]);
    }
}
