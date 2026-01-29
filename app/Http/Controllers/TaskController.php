<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TaskController extends Controller
{
    private function hasTaskAccess(Task $task): bool
    {
        try {
            $userId = Auth::id();
        } catch (\Throwable $th) {
            throw $th;
        }

        $project = Project::with(['projectMembers' => function ($query) {
            $query->whereHas('user'); // Only get members with valid users
        }, 'projectMembers.user', 'projectMembers.role'])->find($task->project_id);

        if (!$project) {
            return false;
        }

        $isOwner = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null && $member->role !== null;
            })
            ->where('user_id', $userId)
            ->where('role.name', 'Owner')
            ->isNotEmpty();

        if ($isOwner) {
            return $isOwner;
        }

        $isMember = $task->users()
            ->where('user_id', $userId)
            ->exists();

        return $isMember;
    }

    public function index()
    {
        $userId = Auth::id();
        $tasks = Task::with([
            'users:id,name',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'project:id,title',
            'tags:id,name,severity',
            'subTaskRecursive'
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
                return $task;
            });

        $totalAssigned = $tasks->where('is_assigned', true)->count();

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
            'totalAssigned' => $totalAssigned,
        ];

        return Inertia::render('project/task/Index', Sqids::rec_encode_ids_in_list($response));
    }

    public function store(TaskStoreRequest $request, string $encoded)
    {
        try {
            $projectId = Sqids::decode($encoded);
        } catch (\Exception $e) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        if (!$projectId) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        // Validasi: Cek apakah user adalah anggota project
        $project = Project::with(['projectMembers' => function ($query) {
            $query->whereHas('user');
        }])->find($projectId);

        if (!$project) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $isMember = $project->projectMembers()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return back()->with('error', 'You are not a member of this project');
        }

        $validated = $request->validated();
        $validated['project_id'] = $projectId;
        $validated['parent_id'] = $validated['parent_id'] ?? null;
        $validated['created_by'] = Auth::id();

        $assignUserIds = $validated['assign_users'] ?? [];

        $addTagExist = $validated['add_tag']['exists'] ?? [];
        $addTagNew = [];
        foreach ($validated['add_tag']['new'] ?? [] as $newTag) {
            $tag = Tag::create([
                'name'       => $newTag['name'],
                'severity'   => $newTag['severity']
            ]);

            $addTagNew[] = $tag->id;
        }

        unset($validated['assign_users'], $validated['add_tag']);

        $task = Task::create($validated);

        if (!empty($assignUserIds)) {
            $task->users()->syncWithoutDetaching($assignUserIds);
        }

        if (!empty($addTagExist)) {
            $task->tags()->syncWithoutDetaching($addTagExist);
        }

        if (!empty($addTagNew)) {
            $task->tags()->syncWithoutDetaching($addTagNew);
        }

        $parent = $task->parent;
        while ($parent) {
            $parent->update([
                'progress' => $parent->calculateProgress()
            ]);
            $parent = $parent->parent;
        }

        if (!empty($assignUserIds)) {
            $notification = Notification::create([
                'task_id' => $task->id,
                'task_status_id' => $task->status_id,
                'task_type_id' => $task->type_id,
                'message' => "Task '{$task->title}' has been created and assigned to you."
            ]);
            foreach ($assignUserIds as $userId) {
                $notification->users()->attach($userId, ['is_read' => false]);

                // Payload untuk Redis
                $payload = [
                    'id' => Sqids::encode($notification->id),
                    'message' => $notification->message,
                    'task_id' => Sqids::encode($notification->task_id),
                    'is_read' => false,
                ];

                // Simpan ke cache 
                $key = "notifications:user:$userId";
                $existing = Cache::store('redis')->get($key, []);
                $existing[] = $payload; // payload = array notif
                Cache::store('redis')->put(
                    $key,
                    $existing,
                    now()->addMinutes(1)
                );
            }
        }

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task created successfully');
    }

    public function show(string $encoded)
    {
        try {
            $taskId = Sqids::decode($encoded);
        } catch (\Exception $e) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        if (!$taskId) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

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
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->orderBy('id', 'asc')
                    ->with([
                        'user',
                        'replies' => function ($q) {
                            $q->orderBy('id', 'asc');
                        },
                        'replies.user'
                    ]);
            }
        ])->find($taskId);

        if (!$task) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $task->update(['progress' => $task->calculateProgress()]);

        $project = Project::with([
            'projectMembers' => function ($query) {
                $query->whereHas('user'); // Only get members with valid users
            },
            'projectMembers.user:id,name,email',
            'projectMembers.user.media',
            'projectMembers.role:id,name'
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

        $isMember = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', Auth::id())
            ->isNotEmpty();

        $isTaskMember = $task->users()
            ->where('user_id', Auth::id())
            ->exists();

        $isPM = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null && $member->role !== null;
            })
            ->where('user_id', Auth::id())
            ->where('role.name', 'Owner')
            ->isNotEmpty();

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

        $data = [
            'task' => $task->toArray(),
            'project' => $task->project?->toArray(),
            'subTasks' => $task->subTaskRecursive?->toArray() ?? [],
            'assignedUsers' => $assignedUsers,
            'assignableUsers' => $assignableUsers,
            'statuses' => MsTaskStatus::select('id', 'name', 'severity')->get()->toArray(),
            'priorities' => MsTaskPriority::select('id', 'name', 'severity')->get()->toArray(),
            'types' => MsTaskType::select('id', 'name', 'severity')->get()->toArray(),
            'isPM' => $isPM,
            'isMember' => $isMember,
            'isTaskMember' => $isTaskMember,
            'comments' => $task->comments?->toArray() ?? [],
        ];

        return Inertia::render('project/task/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function update(TaskStoreRequest $request, string $encoded, string $taskEncoded)
    {
        try {
            $taskId = Sqids::decode($taskEncoded);
        } catch (\Exception $e) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        if (!$taskId) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $task = Task::find($taskId);

        if (!$task) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        if (!self::hasTaskAccess($task)) {
            return back()->with('error', 'You have no access to this task');
        }

        $data = $request->validated();

        $assignUserIds = $data['assign_users'] ?? [];
        $unassignUserIds = $data['unassign_users'] ?? [];

        if (!empty($data['add_tag']['exists'])) {
            $task->tags()->syncWithoutDetaching($data['add_tag']['exists']);
        }

        $newTagIds = [];
        foreach ($data['add_tag']['new'] ?? [] as $newTag) {
            $tag = Tag::create([
                'name'       => $newTag['name'],
                'severity'   => $newTag['severity']
            ]);

            $newTagIds[] = $tag->id;
        }

        if (!empty($newTagIds)) {
            $task->tags()->syncWithoutDetaching($newTagIds);
        }

        if (!empty($data['remove_tag'])) {
            $task->tags()->detach($data['remove_tag']);
        }

        if ($task->children()->exists() && isset($data['progress'])) {
            unset($data['progress']);
        }

        unset(
            $data['assign_users'],
            $data['unassign_users'],
            $data['add_tag'],
            $data['remove_tag'],
            $data['parent_id']
        );

        $task->update($data);

        $notification = Notification::create([
            'task_id' => $task->id,
            'task_status_id' => $task->status_id,
            'task_type_id' => $task->type_id,
            'message' => "Task '{$task->title}' has been updated."
        ]);

        // Get valid user IDs only (filter out deleted users)
        $existingUserIds = $task->users()
            ->whereNotNull('users.id')
            ->pluck('users.id')
            ->toArray();

        $allUserIds = array_merge($existingUserIds, $assignUserIds);

        foreach (array_unique($allUserIds) as $userId) {
            $notification->users()->attach($userId, ['is_read' => false]);

            // Payload untuk Redis
            $payload = [
                'id' => Sqids::encode($notification->id),
                'message' => $notification->message,
                'task_id' => Sqids::encode($notification->task_id),
                'is_read' => false,
            ];


            // Simpan ke cache 
            $key = "notifications:user:$userId";
            $existing = Cache::store('redis')->get($key, []);
            $existing[] = $payload; // payload = array notif
            Cache::store('redis')->put(
                $key,
                $existing,
                now()->addMinutes(1)
            );
        }

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
                $parent->update(['progress' => $parent->calculateProgress()]);
                $parent = $parent->parent;
            }
        }

        return back()->with('success', 'Task updated successfully');
    }

    public function destroy(string $encoded, string $taskEncoded)
    {
        try {
            $projectId = Sqids::decode($encoded);
            $taskId = Sqids::decode($taskEncoded);
        } catch (\Exception $e) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        if (!$projectId || !$taskId) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $task = Task::find($taskId);

        if (!$task) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        if (!self::hasTaskAccess($task)) {
            return back()->with('error', 'You have no access to this task');
        }

        $notification = Notification::create([
            'task_id' => $task->id,
            'task_status_id' => $task->status_id,
            'task_type_id' => $task->type_id,
            'message' => "Task '{$task->title}' has been deleted."
        ]);

        // Get valid user IDs only (filter out deleted users)
        $allUserIds = $task->users()
            ->whereNotNull('users.id')
            ->pluck('users.id')
            ->toArray();

        foreach (array_unique($allUserIds) as $userId) {
            $notification->users()->attach($userId, ['is_read' => false]);

            // Payload untuk Redis
            $payload = [
                'id' => Sqids::encode($notification->id),
                'message' => $notification->message,
                'task_id' => Sqids::encode($notification->task_id),
                'is_read' => false,
            ];


            // Simpan ke cache 
            $key = "notifications:user:$userId";
            $existing = Cache::store('redis')->get($key, []);
            $existing[] = $payload; // payload = array notif
            Cache::store('redis')->put(
                $key,
                $existing,
                now()->addMinutes(1)
            );
        }

        $task->delete();

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task deleted successfully');
    }
}
