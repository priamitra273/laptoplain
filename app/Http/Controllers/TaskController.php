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

        $task = Task::create($validated);
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
            'project:id,title',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'users:id,name',
            'parent',
            'parent.status:id,name,severity',
            'parent.priority:id,name,severity',
            'parent.type:id,name,severity',
            'parent.users:id,name',
            'subTaskRecursive',
            'subTaskRecursive.status:id,name,severity',
            'subTaskRecursive.priority:id,name,severity',
            'subTaskRecursive.type:id,name,severity',
            'subTaskRecursive.users:id,name',
        ])->findOrFail($taskId);

        // Hitung progress
        $task->update(['progress' => $task->calculateProgress()]);

        // Ambil project & assignable users
        $project = Project::with(['projectMembers.user:id,name,email', 'projectMembers.role:id,name'])
            ->findOrFail($task->project_id);

        $assignableUsers = collect($project->projectMembers)
            ->pluck('user')
            ->unique('id')
            ->values()
            ->toArray();

        // Cek PM
        $isPM = $project->projectMembers
            ->where('user.id', Auth::id())
            ->where('role.name', 'Project Manager')
            ->isNotEmpty();

        // PROPS YANG BENAR UNTUK VUE
        $data = [
            'task' => $task->toArray(),
            'project' => $task->project?->toArray(),
            'subTasks' => $task->subTaskRecursive?->toArray() ?? [],
            'assignedUsers' => $task->users?->toArray() ?? [],
            'assignableUsers' => $assignableUsers,
            'statuses' => MsTaskStatus::select('id', 'name', 'severity')->get()->toArray(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get()->toArray(),
            'types' => MsTaskType::select('id', 'name', 'severity')->get()->toArray(),
            'isPM' => $isPM,
        ];

        // HANYA ENCODE ID (BUKAN severity)
        return Inertia::render('project/task/Detail', Sqids::rec_encode_ids_in_list($data));
    }


    public function update(TaskStoreRequest $request, string $encoded, string $taskEncoded)
    {
        $taskId = Sqids::decode($taskEncoded);
        $task = Task::findOrFail($taskId);

        $data = $request->validated();

        unset($data['parent_id']);

        $progressInput = $data['progress'] ?? null;
        $hasChildren = $task->children()->exists();

        if ($hasChildren) {
            unset($data['progress']);
        } else {
            if ($progressInput === null || $progressInput == $task->progress) {
                unset($data['progress']);
            }
        }

        $task->update($data);

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
