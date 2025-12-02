<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $tasks = Task::with([
            'users:id,name',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title',
            'subTaskRecursive'
        ])
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhereHas('users', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->orderBy('id')
            ->get()
            ->map(function ($task) use ($userId) {
                $task->is_assigned = $task->users->contains('id', $userId) && $task->created_by != $userId;
                $task->is_created_by_me = $task->created_by == $userId;
                return $task;
            });

        // Hitung jumlah task yang di-assign ke user saat ini
        $totalAssigned = $tasks->where('is_assigned', true)->count();

        $statuses = MsTaskStatus::select('id', 'name', 'severity')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();
        $projects = Project::select('id', 'title')->get();

        $response = [
            'tasks' => $tasks->toArray(),
            'statuses' => $statuses->toArray(),
            'priorities' => $priorities->toArray(),
            'types' => $types->toArray(),
            'projects' => $projects->toArray(),

            'totalAssigned' => $totalAssigned,
        ];

        return Inertia::render('project/task/Index', Sqids::rec_encode_ids_in_list($response));
    }





    public function store(TaskStoreRequest $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $validated = $request->validated();
        $validated['project_id'] = $projectId;
        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $validated['created_by'] = Auth::id();

        $assignUserIds = $validated['assign_users'] ?? [];
        unset($validated['assign_users']);

        $task = Task::create($validated);

        if (!empty($assignUserIds)) {
            $task->users()->syncWithoutDetaching($assignUserIds);
        }

        // Hitung progress parent task
        $parent = $task->parent;
        while ($parent) {
            $parent->update([
                'progress' => $parent->calculateProgress()
            ]);
            $parent = $parent->parent;
        }

        // Buat notifikasi ke user yang diassign
        if (!empty($assignUserIds)) {
            $notification = Notification::create([
                'task_id' => $task->id,
                'task_status_id' => $task->status_id,
                'task_type_id' => $task->type_id,
                'message' => "Task '{$task->title}' telah dibuat dan ditugaskan kepada Anda."
            ]);
            foreach ($assignUserIds as $userId) {
                $notification->users()->attach($userId, ['is_read' => false]);
            }
        }

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task created successfully');
    }

    public function show(string $encoded)
    {
        $taskId = Sqids::decode($encoded);
        if (!$taskId) abort(404);

        $task = Task::with([
            'project:id,title,emoji',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'users:id,name',
            'subTaskRecursive',
            'subTaskRecursive.status:id,name,severity',
            'subTaskRecursive.priority:id,name,severity',
            'subTaskRecursive.type:id,name,severity',
            'subTaskRecursive.users:id,name',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->orderBy('id', 'asc')
                    ->with([
                        'user',
                        'replies' => function ($q) {
                            $q->orderBy('id', 'asc');
                        },
                        'replies.user'
                    ]);
            }
        ])->findOrFail($taskId);

        $task->update(['progress' => $task->calculateProgress()]);

        $project = Project::with(['projectMembers.user:id,name,email', 'projectMembers.role:id,name'])
            ->findOrFail($task->project_id);

        $assignableUsers = collect($project->projectMembers)
            ->pluck('user')
            ->unique('id')
            ->values()
            ->toArray();

        $isPM = $project->projectMembers
            ->where('user.id', Auth::id())
            ->where('role.name', 'Project Manager')
            ->isNotEmpty();

        $data = [
            'currentUserId' => Auth::id(),
            'task' => $task->toArray(),
            'project' => $task->project?->toArray(),
            'subTasks' => $task->subTaskRecursive?->toArray() ?? [],
            'assignedUsers' => $task->users?->toArray() ?? [],
            'assignableUsers' => $assignableUsers,
            'statuses' => MsTaskStatus::select('id', 'name', 'severity')->get()->toArray(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get()->toArray(),
            'types' => MsTaskType::select('id', 'name', 'severity')->get()->toArray(),
            'isPM' => $isPM,
            'comments' => $task->comments?->toArray() ?? [],
        ];

        return Inertia::render('project/task/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function update(TaskStoreRequest $request, string $encoded, string $taskEncoded)
    {
        $taskId = Sqids::decode($taskEncoded);
        $task = Task::findOrFail($taskId);

        $data = $request->validated();

        $assignUserIds = $data['assign_users'] ?? [];
        $unassignUserIds = $data['unassign_users'] ?? [];

        unset($data['assign_users'], $data['unassign_users'], $data['parent_id']);

        $task->update($data);

        // Buat notifikasi
        $notification = Notification::create([
            'task_id' => $task->id,
            'task_status_id' => $task->status_id,
            'task_type_id' => $task->type_id,
            'message' => "Task '{$task->title}' telah diperbarui"
        ]);

        $allUserIds = array_merge(
            $task->users()->pluck('users.id')->toArray(),
            $assignUserIds
        );
        foreach (array_unique($allUserIds) as $userId) {
            $notification->users()->attach($userId, ['is_read' => false]);
        }

        // Assign / unassign users
        foreach ($assignUserIds as $userId) {
            $task->assignUser($userId);
        }
        if (!empty($unassignUserIds)) {
            $task->users()->detach($unassignUserIds);
        }

        // Hitung progress parent jika task child
        $hasChildren = $task->children()->exists();
        if (!$hasChildren && isset($data['progress'])) {
            $parent = $task->parent;
            while ($parent) {
                $parent->update(['progress' => $parent->calculateProgress()]);
                $parent = $parent->parent;
            }
        }

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task updated successfully');
    }

    public function destroy(string $encoded, string $taskEncoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $taskId = Sqids::decode($taskEncoded);
        if (!$taskId) abort(404);

        Task::findOrFail($taskId)->delete();

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task deleted successfully');
    }
}
