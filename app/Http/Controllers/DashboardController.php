<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Get recent projects
        $projects = $this->getRecentProjects($userId);

        // Get recent tasks
        $tasks = $this->getRecentTasks($userId);

        // Get statistics
        $stats = $this->getStatistics($userId);

        // Get team members
        $members = $this->getTeamMembers($userId);

        $data = [
            'projects' => $projects,
            'tasks' => $tasks,
            'stats' => [
                'projects' => [
                    'total' => $stats['totalProjects'],
                    'progress' => round($stats['avgProjectProgress']),
                ],
                'tasks' => [
                    'total' => $stats['totalTasks'],
                    'progress' => round($stats['avgTaskProgress']),
                ],
                'members' => [
                    'total' => $members->count(),
                    'list' => $members->toArray(),
                ],
            ],
        ];

        return Inertia::render('Dashboard', Sqids::rec_encode_ids_in_list($data));
    }

    /**
     * Get user's recent projects
     */
    private function getRecentProjects(int $userId): array
    {
        return Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'projectMembers.user:id,name,email',
            'projectMembers.role:id,name'
        ])
            ->whereHas('projectMembers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Get user's recent tasks
     */
    private function getRecentTasks(int $userId): array
    {
        return Task::with([
            'project:id,title,emoji',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'users:id,name'
        ])
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhereHas('users', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($task) use ($userId) {
                $task->is_assigned = $task->users->contains('id', $userId) && $task->created_by != $userId;
                $task->is_created_by_me = $task->created_by == $userId;
                return $task;
            })
            ->toArray();
    }

    /**
     * Get dashboard statistics
     */
    private function getStatistics(int $userId): array
    {
        // Optimize queries by combining them
        $projectStats = Project::whereHas('projectMembers', fn($q) => $q->where('user_id', $userId))
            ->selectRaw('COUNT(*) as total, AVG(progress) as avg_progress')
            ->first();

        $taskStats = Task::where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
                ->orWhereHas('users', fn($qq) => $qq->where('users.id', $userId));
        })
            ->selectRaw('COUNT(*) as total, AVG(progress) as avg_progress')
            ->first();

        return [
            'totalProjects' => $projectStats->total ?? 0,
            'avgProjectProgress' => $projectStats->avg_progress ?? 0,
            'totalTasks' => $taskStats->total ?? 0,
            'avgTaskProgress' => $taskStats->avg_progress ?? 0,
        ];
    }

    /**
     * Get unique team members from user's projects
     */
    private function getTeamMembers(int $userId)
    {
        return Project::whereHas('projectMembers', fn($q) => $q->where('user_id', $userId))
            ->with('projectMembers.user:id,name,email')
            ->get()
            ->flatMap(fn($project) => $project->projectMembers->pluck('user'))
            ->unique('id')
            ->values();
    }
}
