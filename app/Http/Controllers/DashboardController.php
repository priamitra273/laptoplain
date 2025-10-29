<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticStatusEnum;
use App\Enums\SiteStatus;
use App\Enums\StreamingStatusEnum;
use App\Http\Resources\Project\ProjectStatisticResource;
use App\Http\Resources\Regency\RegencyStatisticResource;
use App\Models\Project;
use App\Models\Regency;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::select([
            'uuid', 
            'name', 
            'start_date', 
            'finish_date',
            'plan_site',
            'plan_cctv',
            'created_at',
            'updated_at'
        ])
        ->orderBy('id')
        ->get();

        return Inertia::render('Dashboard', [
            'projects' => $projects,
            'latestProject' => $projects->last()
        ]);
    }

    public function statistic(Request $request, string $uuid)
    {
        $project = Project::withCount([
                'sites as total_site', 
                'cctv as total_preconfig',
                'sites as total_pending_installation' => fn (Builder $q) => $q->where('site_status_id', '!=', SiteStatus::COMPLETE),
                'sites as total_done_installation' => fn (Builder $q) => $q->where('site_status_id', SiteStatus::COMPLETE),
                'cctv as total_config_streaming' => fn (Builder $q) => $q->whereNotNull('streaming_status_id'),
                'cctv as total_pending_streaming' => function (Builder $query) {
                    return $query->where('streaming_status_id', '!=', StreamingStatusEnum::COMPLETE);
                },
                'cctv as total_done_streaming' => function (Builder $query) {
                    return $query->where('streaming_status_id', StreamingStatusEnum::COMPLETE);
                },
                'cctv as total_config_analytic' => function (Builder $query) {
                    return $query->whereNotNull('analytic_status_id');
                },
                'cctv as total_pending_analytic' => function (Builder $query) {
                    return $query->whereNotNull('analytic_status_id')
                        ->where('analytic_status_id', '!=', AnalyticStatusEnum::COMPLETE);
                },
                'cctv as total_done_analytic' => function (Builder $query) {
                    return $query->where('analytic_status_id', AnalyticStatusEnum::COMPLETE);
                },
            ])
            ->whereUuid($uuid)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Success get statistic',
            'data' => (new ProjectStatisticResource($project))->resolve()
        ]);
    }

    public function map(Request $request, string $uuid)
    {
        $project = Project::whereUuid($uuid)->firstOrFail();
        $regencies = Regency::withCount(['cctv' => fn ($q) => $q->where('sites.project_id', $project->id)])->get();

        return new RegencyStatisticResource($regencies);
    }
}
