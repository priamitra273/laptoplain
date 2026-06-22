<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Facades\Sqids;
use App\Http\Requests\Task\TaskBulkDestroyRequest;
use App\Http\Requests\Task\TaskMoveRequest;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateParentRequest;
use App\Http\Requests\Task\TaskUpdatePriorityRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Http\Requests\Task\TaskUpdateStatusRequest;
use App\Models\MsTaskStatus;
use App\Models\Task;
use App\Rules\SqidExists;
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

    protected function authorize(string $action, string $encoded_project_id, Task $task): void
    {
        $project = $this->projectService->findByEncodedId($encoded_project_id);

        abort_if($project->id !== $task->project_id, 404);
        abort_if(Auth::user()->cannot($action, $task), 403);
    }

    /**
     * Get the authenticated user's tasks for the "My Task" index (server-driven).
     */
    public function index(Request $request): Response
    {
        $params = $request->only([
            'view', 'per_page', 'search', 'project_id', 'status_id', 'priority_id', 'type_id',
        ]);

        $data = $this->service->indexProps(Auth::id(), $params);

        return Inertia::render('project/task/Index', Sqids::rec_encode_ids_in_list($data));
    }

    /**
     * Paginate a single board column ("Load more") for the "My Task" board.
     */
    public function boardColumn(Request $request): \Illuminate\Http\JsonResponse
    {
        $params = $request->only([
            'status_id', 'page', 'per_page', 'search', 'project_id', 'priority_id', 'type_id',
        ]);

        return response()->json($this->service->boardColumn(Auth::id(), $params));
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
     * Get task comments
     */
    public function comments(Request $request, Task $task)
    {
        abort_if($request->user()->cannot('view', $task), 403);

        $comments = $this->service->getComments($task);

        return response()->json([
            'success' => true,
            'data' => Sqids::rec_encode_ids_in_list($comments->toArray()),
        ]);
    }

    /**
     * Get task parents hierarchy
     */
    public function parents(Request $request, Task $task)
    {
        abort_if($request->user()->cannot('view', $task), 403);

        $parents = $this->service->getParents($task);

        return response()->json([
            'success' => true,
            'data' => Sqids::rec_encode_ids_in_list($parents->toArray()),
        ]);
    }

    public function update_parents(Request $request, Task $task)
    {
        abort_if($request->user()->cannot('update', $task), 403);

        $request->validate([
            'parent_id' => ['required', 'string', new SqidExists(Task::class)],
        ]);

        $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;

        $task->update([
            'parent_id' => $parentId,
        ]);

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
            due_date: $request->has('due_date') ? $request->input('due_date') : $task->due_date
        );

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
        ]);
    }

    public function updatePriority(TaskUpdatePriorityRequest $request, string $projectEncoded, Task $task)
    {
        $this->authorize('update', $projectEncoded, $task);

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
        $this->authorize('update', $encoded, $task);

        $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;

        $this->service->updateParent($task, $parentId);

        return response()->json([
            'success' => true,
            'message' => 'Task parent updated successfully.',
        ]);
    }

    public function move(TaskMoveRequest $request, string $projectEncoded, Task $task): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $projectEncoded, $task);

        $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;

        $this->service->move($task, $parentId, $request->integer('position'));

        return response()->json([
            'success' => true,
            'message' => 'Task moved successfully.',
        ]);
    }

    public function destroy(Request $request, string $encoded, Task $task)
    {
        $this->authorize('delete', $encoded, $task);

        $task->delete();

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task deleted successfully');
    }

    public function bulkDestroy(TaskBulkDestroyRequest $request, string $projectEncoded): \Illuminate\Http\JsonResponse
    {
        $project = $this->projectService->findByEncodedId($projectEncoded);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $deleted = 0;
        $failed = 0;

        foreach ($request->validated()['ids'] as $encodedId) {
            try {
                $task = Task::find(Sqids::decode($encodedId));
            } catch (\Throwable) {
                $task = null;
            }

            if (! $task || $task->project_id !== $project->id || $user->cannot('delete', $task)) {
                $failed++;

                continue;
            }

            $task->delete();
            $deleted++;
        }

        return response()->json([
            'success' => $deleted > 0,
            'deleted' => $deleted,
            'failed' => $failed,
            'message' => $failed === 0
                ? "{$deleted} task(s) deleted successfully."
                : "{$deleted} task(s) deleted, {$failed} could not be deleted.",
        ]);
    }
}
