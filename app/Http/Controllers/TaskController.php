<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Models\Task;
use App\Models\Project;
use App\Models\MsTaskStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskType;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Tampilkan daftar semua task.
     */
    public function index()
    {
        $tasks = Task::with([
                'project:id,title',
                'status:id,name,severity',
                'priority:id,name,severity',
                'type:id,name,severity',
                'owner:id,name,email'
            ])
            ->orderBy('id')
            ->get();

        $tasks->transform(function ($t) {
            $t->encoded = Sqids::encode($t->id);
            return $t;
        });

        return Inertia::render('task/Index', [
            'tasks'      => $tasks,
            'projects'   => Project::select('id', 'title')->get(),
            'statuses'   => MsTaskStatus::select('id', 'name', 'severity')->get(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get(),
            'types'      => MsTaskType::select('id', 'name', 'severity')->get(),
        ]);
    }

    /**
     * Detail satu task.
     */
    public function show(string $encoded)
    {
        $id = Sqids::decode($encoded);

        $task = Task::with([
            'project:id,title,emoji',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'owner:id,name,email',
            'children',
            'comments:id,commentable_type,commentable_id,body,reaction',
            'tags:id,name,severities',
        ])->findOrFail($id);

        $task->encoded = Sqids::encode($task->id);

        return Inertia::render('task/Detail', [
            'task' => $task,
        ]);
    }

    /**
     * Simpan task baru.
     */
    public function store(TaskStoreRequest $request)
    {
        Task::create($request->validated());
        return to_route('task.index');
    }

    /**
     * Update task berdasarkan encoded ID.
     */
    public function update(TaskStoreRequest $request, string $encoded)
    {
        $id = Sqids::decode($encoded);

        $task = Task::findOrFail($id);
        $task->update($request->validated());

        return to_route('task.index');
    }

    /**
     * Hapus task (soft delete).
     */
    public function destroy(string $encoded)
    {
        $id = Sqids::decode($encoded);

        Task::findOrFail($id)->delete();

        return to_route('task.index');
    }
}
