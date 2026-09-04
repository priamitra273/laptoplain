<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $service
    ) {}

    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $projectsTab = $this->service->normalizeProjectTab($request->query('projects_tab'));

        return Inertia::render('dashboard/Dashboard', [
            'summary' => $this->service->summary($userId),
            'stats' => $this->service->taskStats($userId),
            'attention' => $this->service->needsAttention($userId),
            'projectsTab' => $projectsTab,
            'activity' => Inertia::defer(fn () => $this->service->activityGraph($userId)),
            'projectProgress' => Inertia::defer(
                fn () => $this->service->projectProgress($userId, $projectsTab),
                'projects',
            ),
            'latestProjects' => Inertia::defer(fn () => $this->service->latestProjects($userId), 'projects'),
        ]);
    }
}
