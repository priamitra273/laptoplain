<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateParentRequest;
use App\Http\Requests\Task\TaskUpdatePriorityRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Http\Requests\Task\TaskUpdateStatusRequest;
use App\Models\MsTaskStatus;
use App\Models\Task;
use App\Services\ProjectService;
use App\Services\TaskService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function show(string $encoded)
    {
        try {
            $taskId = Sqids::decode($encoded);
            $task = $this->service->getTaskForDetail($taskId, Auth::id());
        } catch (\Exception $e) {
            throw new NotFoundHttpException(404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->cannot('view', $task)) {
            throw new NotFoundHttpException(404);
        }

        $data = $this->service->getTaskDetailProps($task, $user);

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

    public function updateParent(TaskUpdateParentRequest $request, string $encoded, string $taskEncoded)
    {
        try {
            $projectId = Sqids::decode($encoded);
            $taskId = Sqids::decode($taskEncoded);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid task or project id.',
            ], 422);
        }

        $task = Task::where('project_id', $projectId)->find($taskId);

        if (! $task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->can('update', $task)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to move this task.',
            ], 403);
        }

        $parentId = $request->validated('parent_id');
        $oldParentId = $task->parent_id;

        if ($parentId === $task->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task cannot be its own parent.',
            ], 422);
        }

        if ($parentId) {
            $newParent = Task::where('project_id', $projectId)->find($parentId);
            if (! $newParent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target parent task not found in this project.',
                ], 422);
            }

            $cursor = $newParent;
            while ($cursor) {
                if ($cursor->id === $task->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid move: cannot move task under its own descendant.',
                    ], 422);
                }
                $cursor = $cursor->parent;
            }
        }

        $task->update([
            'parent_id' => $parentId,
        ]);

        $recalculateParents = function (?int $startParentId): void {
            if (! $startParentId) {
                return;
            }

            $current = Task::find($startParentId);
            while ($current) {
                $current->update([
                    'progress' => $current->calculateProgress(),
                ]);
                $current = $current->parent;
            }
        };

        $recalculateParents($oldParentId);
        $recalculateParents($parentId);

        return response()->json([
            'success' => true,
            'message' => 'Task parent updated successfully.',
        ]);
    }

    public function destroy(string $encoded, string $taskEncoded)
    {
        try {
            $projectId = Sqids::decode($encoded);
            $taskId = Sqids::decode($taskEncoded);
            $task = Task::with([
                'subTaskRecursive.users:id',
                'users:id',
            ])->withExists([
                'users as is_task_member' => fn ($q) => $q->where('user_id', Auth::id()),

                'project as is_owner' => fn ($q) => $q->whereHas(
                    'projectMembers',
                    fn ($q) => $q->where('user_id', Auth::id())
                        ->whereHas('role', fn ($r) => $r->where('name', 'Owner'))
                ),
            ])->findOrFail($taskId);
        } catch (\Exception $e) {
            return back()->with('error', 'Task not found.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->cannot('delete', $task)) {
            return back()->with('error', 'You do not have permission to delete this task.');
        }

        foreach ($task->subTaskRecursive as $subTask) {
            $allUserIds = $subTask->users->pluck('id')->toArray();

            TaskNotification::createTaskNotification(
                $subTask,
                $allUserIds,
                TaskNotificationType::DELETED
            );

            $subTask->delete();
        }

        $allUserIds = $task->users->pluck('id')->toArray();

        TaskNotification::createTaskNotification(
            $task,
            $allUserIds,
            TaskNotificationType::DELETED
        );

        $parent = $task->parent;

        $task->delete();

        if ($parent) {
            $task->parent()->dissociate();
            if ($parent->children()->exists()) {
                $parent->update(['progress' => $parent->calculateProgress()]);
            } else {
                $parent->update(['progress' => $parent->status->score]);
            }
        }

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task deleted successfully');
    }

    /**
     * Find task by encoded id
     */
    protected function findByEncodedId(string $encoded): Task
    {
        try {
            $taskId = Sqids::decode($encoded);
            $task = Task::findOrFail($taskId);
        } catch (\Exception $e) {
            abort(404);
        }

        return $task;
    }
}
