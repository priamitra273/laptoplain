<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Models\Task;
use App\Models\MsTaskStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskType;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with([
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


    public function store(TaskStoreRequest $request)
    {
        Task::create($request->validated());
        return to_route('task.index');
    }


    public function update(TaskStoreRequest $request, string $encoded)
    {
        $id = Sqids::decode($encoded);

        $task = Task::findOrFail($id);
        $task->update($request->validated());

        return to_route('task.index');
    }

    public function destroy(string $encoded)
    {
        $id = Sqids::decode($encoded);

        Task::findOrFail($id)->delete();

        return to_route('task.index');
    }
}
