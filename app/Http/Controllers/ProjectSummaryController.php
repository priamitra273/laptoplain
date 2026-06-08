<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProjectSummaryController extends Controller
{
    public function __invoke(string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        $project   = Project::findOrFail($projectId);

        if (Auth::user()->cannot('view', $project)) abort(403);

        $tasks = Task::with(['status', 'priority', 'category', 'users:id,name', 'users.media'])
            ->where('project_id', $projectId)
            ->get();

        // 1. Status Overview (untuk pie/donut chart)
        $statusOverview = $tasks->groupBy('status.name')
            ->map(fn($group, $name) => [
                'status' => $name,
                'count'  => $group->count(),
                'color'  => $group->first()->status?->severity,
            ])->values();

        // 2. Priority Breakdown
        $priorityBreakdown = $tasks->groupBy('priority.name')
            ->map(fn($group, $name) => [
                'priority' => $name,
                'count'    => $group->count(),
            ])->values();

        // 3. Category of Work (Epic/Story/Issue count)
        $categoryBreakdown = $tasks->groupBy('category.name')
            ->map(fn($group, $name) => [
                'category' => $name ?? 'Uncategorized',
                'count'    => $group->count(),
            ])->values();

        // 4. Team Workload (berapa task per user)
        $teamWorkload = $tasks->flatMap(fn($task) => $task->users)
            ->groupBy('id')
            ->map(fn($userTasks, $userId) => [
                'user'       => $userTasks->first()->only(['id', 'name']),
                'avatar_url' => $userTasks->first()->avatar_url,
                'total'      => $userTasks->count(),
                'completed'  => $tasks->filter(
                    fn($t) => $t->users->contains('id', $userId)
                        && strtoupper($t->status?->name) === 'COMPLETED'
                )->count(),
            ])->values();

        // 5. Epic Progress
        $epicProgress = Task::with(['children.status', 'category'])
            ->where('project_id', $projectId)
            ->epics()
            ->get()
            ->map(fn($epic) => [
                'id'       => $epic->id,
                'title'    => $epic->title,
                'progress' => $epic->calculateProgress(),
                'total'    => $epic->children()->count(),
                'done'     => $epic->children()
                    ->whereHas('status', fn($q) => $q->where('name', 'Completed'))
                    ->count(),
            ]);

        // 6. Recent Activity (10 task terakhir diupdate)
        $recentActivity = Task::with(['status', 'users:id,name', 'users.media'])
            ->where('project_id', $projectId)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn($task) => [
                'id'         => $task->id,
                'title'      => $task->title,
                'status'     => $task->status?->name,
                'updated_at' => $task->updated_at,
                'users'      => $task->users->map(fn($u) => [
                    'name'       => $u->name,
                    'avatar_url' => $u->avatar_url,
                ]),
            ]);

        return response()->json(
            Sqids::rec_encode_ids_in_list([
                'statusOverview'    => $statusOverview,
                'priorityBreakdown' => $priorityBreakdown,
                'categoryBreakdown' => $categoryBreakdown,
                'teamWorkload'      => $teamWorkload,
                'epicProgress'      => $epicProgress,
                'recentActivity'    => $recentActivity,
            ])
        );
    }
}
