<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with([
            'users:id,name',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title',
            'subTaskRecursive'
        ])
            ->where('created_by', Auth::id())
            ->orderBy('id')
            ->get();


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
        ];

        return Inertia::render('task/Index', Sqids::rec_encode_ids_in_list($response));
    }


    public function store(TaskStoreRequest $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $validated = $request->validated();

        $validated['project_id'] = $projectId;

        if (!isset($validated['parent_id'])) {
            $validated['parent_id'] = null;
        }

        $validated['created_by'] = Auth::id();

        $assignUserIds = $validated['assign_users'] ?? [];

        unset($validated['assign_users']);

        $task = Task::create($validated);

        if (!empty($assignUserIds)) {
            $task->users()->syncWithoutDetaching($assignUserIds);
        }

        $parent = $task->parent;
        while ($parent) {
            $parent->update([
                'progress' => $parent->calculateProgress()
            ]);
            $parent = $parent->parent;
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
                    ->orderBy('id', 'asc')        // urut parent
                    ->with([
                        'user',
                        'replies' => function ($q) {
                            $q->orderBy('id', 'asc'); // urut child
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

        foreach ($assignUserIds as $userId) {
            $task->assignUser($userId);
        }

        if (!empty($unassignUserIds)) {
            $task->users()->detach($unassignUserIds);
        }

        $hasChildren = $task->children()->exists();

        if (!$hasChildren && isset($data['progress'])) {
            $parent = $task->parent;

            while ($parent) {
                $parent->update([
                    'progress' => $parent->calculateProgress()
                ]);

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
            ->with('success', 'Task updated successfully');
    }
}
