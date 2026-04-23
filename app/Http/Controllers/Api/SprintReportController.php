<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Services\SprintReportService;
use Illuminate\Http\JsonResponse;

class SprintReportController extends Controller
{
    public function __construct(
        protected SprintReportService $service
    ) {}

    public function statusReport(Project $project, ProjectSprint $projectSprint): JsonResponse
    {
        abort_if($project->id !== $projectSprint->project_id, 404);

        $report = $this->service->getSprintStatusReport($projectSprint);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function burndown(Project $project, ProjectSprint $projectSprint): JsonResponse
    {
        abort_if($project->id !== $projectSprint->project_id, 404);

        $data = $this->service->getBurndownChartData($projectSprint);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
