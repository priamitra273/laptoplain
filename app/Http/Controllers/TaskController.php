<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateParentRequest;
use App\Http\Requests\Task\TaskUpdatePriorityRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Http\Requests\Task\TaskUpdateStatusRequest;
use App\Models\MsTaskStatus;
use App\Models\Task;
use App\Services\ProjectService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $service,
        private ProjectService $projectService
    ) {}

    /**
     * Get all tasks for the authenticated user.
     */
    public function index(): Response
    {
        $data = $this->service->indexProps(Auth::id());

        return Inertia::render('project/task/Index', Sqids::rec_encode_ids_in_list($data));
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(TaskStoreRequest $request, string $encoded, CreateTaskAction $createTaskAction)
    {
        $project = $this->projectService->findByEncodedId($encoded);

        if ($request->user()->cannot('create', [Task::class, $project])) {
            return back()->with('error', 'You do not have permission to create a task in this project.');
        }

        $createTaskAction->execute($project, $request->validated(), Auth::id());

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task created successfully');
    }

    /**
     * Show the spesific resource.
     */
    public function show(Request $request, Task $task)
    {
        abort_if($request->user()->cannot('view', $task), 403);

        $data = $this->service->getTaskDetailProps(
            $this->service->getTaskForDetail($task->id, Auth::id()),
            $request->user()
        );

        return Inertia::render('project/task/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function update(TaskUpdateRequest $request, string $encoded, string $taskEncoded, UpdateTaskAction $updateTaskAction)
    {
        try {
            $taskId = Sqids::decode($taskEncoded);
            $task = Task::with([
                'users:id',
                'project.projectMembers.user:id',
                'project.projectMembers.role:id,name',
            ])->withExists([
                'users as is_task_member' => fn ($q) => $q->where('user_id', Auth::id()),

                'project as is_owner' => function ($q) {
                    $q->whereHas('projectMembers', function ($q) {
                        $q->where('user_id', Auth::id())
                            ->whereHas('role', fn ($r) => $r->where('name', 'Owner'));
                    });
                },
            ])->findOrFail($taskId);
        } catch (\Exception $e) {
            return back()->with('error', 'Task not found.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->cannot('update', $task)) {
            return back()->with('error', 'You do not have permission to update this task.');
        }

        $updateTaskAction->execute($task, $request->validated());

        return back()->with('success', 'Task updated successfully');
    }

    /**
     * Update task status
     */
    public function updateStatus(TaskUpdateStatusRequest $request, Task $task)
    {
        if ($request->user()->cannot('update', $task)) {
            abort(403);
        }

        $this->service->updateStatus(
            task: $task,
            status: MsTaskStatus::find(Sqids::decode($request->status_id)),
            due_date: $request->due_date
        );

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
        ]);
    }

    public function updatePriority(TaskUpdatePriorityRequest $request, string $projectEncoded, Task $task)
    {
        $project = $this->projectService->findByEncodedId($projectEncoded);

        abort_if($project->id !== $task->project_id, 404);
        abort_if($request->user()->cannot('update', $task), 403);

        $task->update([
            'priority_id' => Sqids::decode($request->priority_id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task priority updated successfully',
            'data' => [
                'task_id' => Sqids::encode($task->id),
                'priority_id' => $request->input('priority_id'),
            ],
        ]);
    }

    /**
     * Update parent resource.
     */
    public function updateParent(TaskUpdateParentRequest $request, string $encoded, Task $task)
    {
        $project = $this->projectService->findByEncodedId($encoded);

        abort_if($project->id !== $task->project_id, 404);
        abort_if($request->user()->cannot('update', $task), 403);

        $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;

        $this->service->updateParent($task, $parentId);

        return response()->json([
            'success' => true,
            'message' => 'Task parent updated successfully.',
        ]);
    }

    public function destroy(Request $request, string $encoded, Task $task)
    {
        $project = $this->projectService->findByEncodedId($encoded);

        abort_if($project->id !== $task->project_id, 404);
        abort_if($request->user()->cannot('delete', $task), 403);

        $task->delete();

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task deleted successfully');
    }
}
