<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateParentRequest;
use App\Http\Requests\Task\TaskUpdatePriorityRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Http\Requests\Task\TaskUpdateStatusRequest;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskCategory;
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

    public function index(): Response
    {
        $data = $this->service->indexProps(Auth::id());

        return Inertia::render('project/task/Index', Sqids::rec_encode_ids_in_list($data));
    }

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

            $task = Task::with([
                'project:id,title,emoji',

                'status:id,name,severity',
                'priority:id,name,severity',
                'type:id,name,severity',
                'users:id,name',
                'users.media',

                'tags:id,name,severity',

                'subTaskRecursive',
                'subTaskRecursive.status:id,name,severity',
                'subTaskRecursive.priority:id,name,severity',
                'subTaskRecursive.type:id,name,severity',
                'subTaskRecursive.category:id,name,icon,severity',
                'subTaskRecursive.users:id,name',
                'creator:id,name', // Add creator relationship
                'creator.media',
                'comments' => function ($query) {
                    $query->whereNull('parent_id')
                        ->orderBy('id', 'asc')
                        ->with([
                            'user',
                            'replies' => function ($q) {
                                $q->orderBy('id', 'asc');
                            },
                            'replies.user',
                        ]);
                },
            ])->withExists([
                'project as is_project_member' => function ($q) {
                    $q->whereHas('projectMembers', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
                },
            ])->findOrFail($taskId);
        } catch (\Exception $e) {
            throw new NotFoundHttpException(404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->cannot('view', $task)) {
            throw new NotFoundHttpException(404);
        }

        $task->update(['progress' => $task->calculateProgress()]);

        $project = $task->project;

        // Get assignable users with avatar_url - filter out null users
        $assignableUsers = collect($project->projectMembers)
            ->filter(fn ($member) => $member->user !== null)
            ->map(fn ($member) => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'avatar_url' => $member->user->avatar_url,
            ])
            ->unique('id')
            ->values()
            ->toArray();

        // Format assigned users with avatar_url
        $assignedUsers = $task->users
            ->filter(fn ($user) => $user !== null)
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url ?? null,
            ])
            ->values()
            ->toArray();

        // Format creator with avatar_url
        $creator = null;
        if ($task->creator) {
            $creator = [
                'id' => $task->creator->id,
                'name' => $task->creator->name,
                'avatar_url' => $task->creator->avatar_url ?? null,
            ];
        }

        $isOwner = $task->project
            ->projectMembers
            ->contains(function ($member) use ($user) {
                return $member->user_id === $user->id
                    && $member->role?->name === 'Owner';
            });

        $isTaskMember = $task->users
            ->contains('id', $user->id);

        $data = [
            'task' => $task->toArray(),
            'project' => $project->toArray(),
            'assignedUsers' => $assignedUsers,
            'assignableUsers' => $assignableUsers,
            'creator' => $creator,
            'statuses' => MsTaskStatus::select('id', 'name', 'severity')->get()->toArray(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get()->toArray(),
            'types' => MsTaskType::select('id', 'name', 'severity')->get()->toArray(),
            'categories' => TaskCategory::select('id', 'name', 'icon', 'severity')->get()->toArray(),
            'isTaskMember' => $isTaskMember,
            'isOwner' => $isOwner,
            'comments' => $task->comments?->toArray() ?? [],
        ];

        return Inertia::render('project/task/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function update(TaskUpdateRequest $request, string $encoded, string $taskEncoded)
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

        $data = $request->validated();

        if (isset($data['status_id']) && $data['status_id'] !== $task->status_id) {

            $completedStatusId = MsTaskStatus::where('name', 'Completed')->value('id');

            if ((int) $data['status_id'] === (int) $completedStatusId) {
                $data['completed_at'] = now();

                if (! $task->children()->exists()) {
                    $data['progress'] = 100;
                }
            } else {
                $data['completed_at'] = null;
            }
        }
        $assignUserIds = $data['assign_users'] ?? [];
        $unassignUserIds = $data['unassign_users'] ?? [];

        if (! empty($data['add_tag']['exists'])) {
            $task->tags()->syncWithoutDetaching($data['add_tag']['exists']);
        }

        $newTagIds = [];
        foreach ($data['add_tag']['new'] ?? [] as $newTag) {
            $tag = Tag::create([
                'name' => $newTag['name'],
                'severity' => $newTag['severity'],
            ]);

            $newTagIds[] = $tag->id;
        }

        if (! empty($newTagIds)) {
            $task->tags()->syncWithoutDetaching($newTagIds);
        }

        if (! empty($data['remove_tag'])) {
            $task->tags()->detach($data['remove_tag']);
        }

        if (
            isset($data['status_id']) &&
            $data['status_id'] !== $task->status_id &&
            ! isset($data['completed_at'])
        ) {
            $taskStatus = MsTaskStatus::find($data['status_id']);
            $data['progress'] = $taskStatus ? $taskStatus->score : 0;
        }

        if ($task->children()->exists() && isset($data['progress'])) {
            unset($data['progress']);
        }

        unset(
            $data['assign_users'],
            $data['unassign_users'],
            $data['add_tag'],
            $data['remove_tag'],
        );

        $task->update($data);

        $existingUserIds = $task->users()
            ->whereNotNull('users.id')
            ->pluck('users.id')
            ->toArray();

        $allUserIds = array_merge($existingUserIds, $assignUserIds);

        foreach ($assignUserIds as $userId) {
            $task->assignUser($userId);
        }

        if (! empty($unassignUserIds)) {
            $task->users()->detach($unassignUserIds);
        }

        $hasChildren = $task->children()->exists();
        if (! $hasChildren && isset($data['progress'])) {
            $parent = $task->parent;
            while ($parent) {
                $parent->update(['progress' => $parent->calculateProgress()]);
                $parent = $parent->parent;
            }
        }

        try {
            TaskNotification::createTaskNotification(
                $task,
                $allUserIds,
                TaskNotificationType::UPDATED
            );
        } catch (\Throwable $th) {
            // throw $th;
        }

        return back()->with('success', 'Task updated successfully');
    }

    /**
     * Update task status
     */
    public function updateStatus(TaskUpdateStatusRequest $request, string $encoded)
    {
        $task = $this->findByEncodedId($encoded);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->cannot('update', $task)) {
            abort(403);
        }

        $status = $request->status;
        $this->service->updateStatus($task, $status);

        if ($request->due_date) {
            $task->update(['due_date' => $request->due_date]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
        ]);
    }

    public function updatePriority(TaskUpdatePriorityRequest $request, string $projectEncoded, string $taskEncoded)
    {
        try {
            $projectId = Sqids::decode($projectEncoded);
            $taskId = Sqids::decode($taskEncoded);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid task or project id.',
            ], 422);
        }

        if (! $projectId || ! $taskId) {
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
        if ($user->cannot('update', $task)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this task.',
            ], 403);
        }

        $task->update([
            'priority_id' => $request->priorityId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task priority updated successfully',
            'data' => [
                'task_id' => $taskEncoded,
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
