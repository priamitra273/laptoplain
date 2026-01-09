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
use Inertia\Inertia;

class TaskController extends Controller
{
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
        $project = Project::with('projectMembers')->find($projectId);

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
                'message' => "Task '{$task->title}' telah dibuat dan ditugaskan kepada Anda."
            ]);
            foreach ($assignUserIds as $userId) {
                $notification->users()->attach($userId, ['is_read' => false]);
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

        $project = Project::with(['projectMembers.user:id,name,email', 'projectMembers.role:id,name'])
            ->findOrFail($task->project_id);

        $assignableUsers = collect($project->projectMembers)
            ->pluck('user')
            ->unique('id')
            ->values()
            ->toArray();

        $isMember = $project->projectMembers
            ->where('user.id', Auth::id())
            ->isNotEmpty();

        $isPM = $project->projectMembers
            ->where('user.id', Auth::id())
            ->where('role.name', 'Project Manager')
            ->isNotEmpty();

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
            'isMember' => $isMember,
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

        // Validasi: Cek apakah user adalah anggota project
        $project = Project::with('projectMembers')->findOrFail($task->project_id);
        $isMember = $project->projectMembers()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return back()->with('error', 'You are not a member of this project');
        }

        $data = $request->validated();

        $assignUserIds = $data['assign_users'] ?? [];
        $unassignUserIds = $data['unassign_users'] ?? [];

        // ==============================
        // HANDLE TAGGING
        // ==============================

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
            'message' => "Task '{$task->title}' telah diperbarui"
        ]);

        $allUserIds = array_merge(
            $task->users()->pluck('users.id')->toArray(),
            $assignUserIds
        );
        foreach (array_unique($allUserIds) as $userId) {
            $notification->users()->attach($userId, ['is_read' => false]);
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

        $redirectTo = $request->input('redirect_to')
            ?? route('task.show', Sqids::encode($task->id));

        return redirect($redirectTo)
            ->with('success', 'Task updated successfully');
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
        $project = Project::with('projectMembers')->find($task->project_id);

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

        $notification = Notification::create([
            'task_id' => $task->id,
            'task_status_id' => $task->status_id,
            'task_type_id' => $task->type_id,
            'message' => "Task '{$task->title}' telah dihapus"
        ]);

        $allUserIds = $task->users()->pluck('users.id')->toArray();
        foreach (array_unique($allUserIds) as $userId) {
            $notification->users()->attach($userId, ['is_read' => false]);
        }

        $task->delete();

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Task deleted successfully');
    }
}
