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
