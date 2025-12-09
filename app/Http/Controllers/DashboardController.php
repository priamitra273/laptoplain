<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
use App\Models\MsTaskStatus;
use App\Models\MsProjectStatus;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $projects = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'projectMembers.user:id,name,email',
            'projectMembers.role:id,name'
        ])
            ->whereHas('projectMembers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $tasks = Task::with([
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
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($task) use ($userId) {
                $task->is_assigned = $task->users->contains('id', $userId) && $task->created_by != $userId;
                $task->is_created_by_me = $task->created_by == $userId;
                return $task;
            });

        $completedTaskStatusId = MsTaskStatus::where('name', 'Completed')->first()->id ?? 0;

        $totalTasks = Task::where(function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->orWhereHas('users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                });
        })->count();

        $completedTasks = Task::where(function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->orWhereHas('users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                });
        })
            ->where('status_id', $completedTaskStatusId)
            ->count();

        $inProgressTasks = $totalTasks - $completedTasks;

        $completedProjectStatusId = MsProjectStatus::where('name', 'Completed')->first()->id ?? 0;

        $totalProjects = Project::whereHas('projectMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();

        $completedProjects = Project::whereHas('projectMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('status_id', $completedProjectStatusId)
            ->count();

        $inProgressProjects = $totalProjects - $completedProjects;

        $members = Project::whereHas('projectMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with('projectMembers.user')
            ->get()
            ->flatMap(fn($project) => $project->projectMembers->pluck('user'))
            ->unique('id')
            ->values();

        $totalMembers = $members->count();

        $data = [
            'projects' => $projects->toArray(),
            'tasks' => $tasks->toArray(),
            'stats' => [
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'in_progress' => $inProgressTasks,
                ],
                'projects' => [
                    'total' => $totalProjects,
                    'completed' => $completedProjects,
                    'in_progress' => $inProgressProjects,
                ],
                'members' => [
                    'total' => $totalMembers,
                    'list' => $members->toArray(),
                ],
            ],
        ];

        return Inertia::render('Dashboard', Sqids::rec_encode_ids_in_list($data));
    }
}
