<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\ProjectSprint;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProjectReportController extends Controller
{
    public function __invoke(string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        $project   = Project::findOrFail($projectId);

        if (Auth::user()->cannot('view', $project)) abort(403);

        $sprints = ProjectSprint::with([
            'status',
            'tasks.status',
            'tasks' => fn($q) => $q->withPivot(['created_at']),
        ])
            ->where('project_id', $projectId)
            ->whereHas('status', fn($q) => $q->whereIn('name', ['Active', 'Completed']))
            ->orderBy('order')
            ->get();

        $burndownData = $sprints->map(function ($sprint) {
            $start   = $sprint->start_date ?? Carbon::now()->subDays(14);
            $end     = $sprint->end_date   ?? Carbon::now();
            $tasks   = $sprint->tasks;
            $total   = $tasks->count();

            // Generate daily data points
            $days = [];
            $current = $start->copy();

            while ($current->lte($end)) {
                $completedByDay = $tasks->filter(
                    fn($t) => $t->completed_at && Carbon::parse($t->completed_at)->lte($current)
                )->count();

                $days[] = [
                    'date'      => $current->toDateString(),
                    'ideal'     => round($total - ($total * ($current->diffInDays($start) / max($start->diffInDays($end), 1))), 2),
                    'remaining' => $total - $completedByDay,
                ];

                $current->addDay();
            }

            return [
                'sprint_id'   => $sprint->id,
                'sprint_name' => $sprint->name,
                'status'      => $sprint->status?->name,
                'total_tasks' => $total,
                'data'        => $days,
            ];
        });

        // Backlog report
        $backlogTasks = \App\Models\Task::with(['status', 'priority', 'category'])
            ->where('project_id', $projectId)
            ->backlog()
            ->orderBy('id')
            ->get();

        return response()->json(
            Sqids::rec_encode_ids_in_list([
                'burndown' => $burndownData,
                'backlog'  => $backlogTasks->toArray(),
            ])
        );
    }
}
