<?php

namespace App\Http\Controllers;

use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Http\Requests\Task\TaskUpdateStatusRequest;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $service
    ) {}

    public function index()
    {
        $userId = Auth::id();
        $tasks = Task::with([
            'users:id,name',
            'users.media',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title',
            'tags:id,name,severity',
            'subTaskRecursive',
            'creator:id,name', // Add creator relationship
            'creator.media',
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

                // Format creator with avatar
                if ($task->creator) {
                    $task->creator->avatar_url = $task->creator->avatar_url;
                }

                return $task;
            });

        $totalAssigned = $tasks->where('is_assigned', true)->count();

        $statuses = MsTaskStatus::select('id', 'name', 'severity')->orderBy('id')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();
        $projects = Project::visibleFor(Auth::user())
            ->select('id', 'title')
            ->get();

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
        try {
            $projectId = Sqids::decode($encoded);
        } catch (\Exception $e) {
            return back()->with('error', 'Project not found.');
        }

        if (! $projectId) {
            return back()->with('error', 'Project not found.');
        }

        // Validasi: Cek apakah user adalah anggota project
        $project = Project::with(['projectMembers' => function ($query) {
            $query->whereHas('user');
        }])->find($projectId);

        if (! $project) {
            return back()->with('error', 'Project not found.');
        }

        $user = Auth::user();
        if ($user->cannot('create', [Task::class, $project])) {
            return back()->with('error', 'You do not have permission to create a task in this project.');
        }

        $validated = $request->validated();
        $validated['project_id'] = $projectId;
        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $validated['created_by'] = Auth::id();

        $assignUserIds = $validated['assign_users'] ?? [];

        if (! in_array(Auth::id(), $assignUserIds)) {
            $assignUserIds[] = Auth::id();
        }

        $addTagExist = $validated['add_tag']['exists'] ?? [];
        $addTagNew = [];
        foreach ($validated['add_tag']['new'] ?? [] as $newTag) {
            $tag = Tag::create([
                'name' => $newTag['name'],
                'severity' => $newTag['severity'],
            ]);

            $addTagNew[] = $tag->id;
        }

        $taskStatus = MsTaskStatus::find($validated['status_id']);
        $progress = $taskStatus ? $taskStatus->score : 0;
        $validated['progress'] = $progress;

        unset($validated['assign_users'], $validated['add_tag']);

        $task = Task::create($validated);

        if (! empty($assignUserIds)) {
            $task->users()->syncWithoutDetaching($assignUserIds);
        }

        if (! empty($addTagExist)) {
            $task->tags()->syncWithoutDetaching($addTagExist);
        }

        if (! empty($addTagNew)) {
            $task->tags()->syncWithoutDetaching($addTagNew);
        }

        $parent = $task->parent;
        while ($parent) {
            $parent->update([
                'progress' => $parent->calculateProgress(),
            ]);
            $parent = $parent->parent;
        }

        if (! empty($assignUserIds)) {
            try {
                TaskNotification::createTaskNotification(
                    $task,
                    $assignUserIds,
                    TaskNotificationType::CREATED
                );
            } catch (\Throwable $th) {
                // throw $th;
            }
        }

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
            ])->findOrFail($taskId);
        } catch (\Exception $e) {
            throw new NotFoundHttpException(404);
        }

        $user = Auth::user();
        if ($user->cannot('view', $task)) {
            throw new NotFoundHttpException(404);
        }

        $task->update(['progress' => $task->calculateProgress()]);

        $project = Project::with([
            'projectMembers.user:id,name,email',
            'projectMembers.user.media',
            'projectMembers.role:id,name',
        ])->findOrFail($task->project_id);

        // Get assignable users with avatar_url - filter out null users
        $assignableUsers = collect($project->projectMembers)
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->map(function ($member) {
                return [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'avatar_url' => $member->user->avatar_url,
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();

        $isTaskMember = $task->users()
            ->where('user_id', Auth::id())
            ->exists();

        // Format assigned users with avatar_url
        $assignedUsers = $task->users
            ->filter(function ($user) {
                return $user !== null;
            })
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url ?? null,
                ];
            })
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

        $data = [
            'task' => $task->toArray(),
            'project' => $project->toArray(),
            'assignedUsers' => $assignedUsers,
            'assignableUsers' => $assignableUsers,
            'creator' => $creator, // Add creator to response
            'statuses' => MsTaskStatus::select('id', 'name', 'severity')->get()->toArray(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get()->toArray(),
            'types' => MsTaskType::select('id', 'name', 'severity')->get()->toArray(),
            'isTaskMember' => $isTaskMember,
            'comments' => $task->comments?->toArray() ?? [],
        ];

        return Inertia::render('project/task/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function update(TaskUpdateRequest $request, string $encoded, string $taskEncoded)
    {
        try {
            $taskId = Sqids::decode($taskEncoded);
            $task = Task::findOrFail($taskId);
        } catch (\Exception $e) {
            return back()->with('error', 'Task not found.');
        }

        $user = Auth::user();
        if ($user->cannot('update', $task)) {
            return back()->with('error', 'You do not have permission to update this task.');
        }

        $data = $request->validated();

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

        if ($data['status_id'] && $data['status_id'] !== $task->status_id) {
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

        if (Auth::user()->cannot('update', $task)) {
            abort(403);
        }

        $status = MsTaskStatus::find(Sqids::decode($request->status_id));
        $this->service->updateStatus($task, $status);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
        ]);
    }

    public function destroy(string $encoded, string $taskEncoded)
    {
        try {
            $projectId = Sqids::decode($encoded);
            $taskId = Sqids::decode($taskEncoded);
            $task = Task::findOrFail($taskId);
        } catch (\Exception $e) {
            return back()->with('error', 'Task not found.');
        }

        foreach ($task->subTaskRecursive as $subTask) {
            $allUserIds = $subTask->users()
                ->whereNotNull('users.id')
                ->pluck('users.id')
                ->toArray();

            TaskNotification::createTaskNotification(
                $subTask,
                $allUserIds,
                TaskNotificationType::DELETED
            );

            $subTask->delete();
        }

        $user = Auth::user();
        if ($user->cannot('delete', $task)) {
            return back()->with('error', 'You do not have permission to delete this task.');
        }

        $allUserIds = $task->users()
            ->whereNotNull('users.id')
            ->pluck('users.id')
            ->toArray();

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
