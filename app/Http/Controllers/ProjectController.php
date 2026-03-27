<?php

namespace App\Http\Controllers;

use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Http\Requests\Project\ProjectUpdateRequest;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectSprint;    // ✅ BARU
use App\Models\Task;             // ✅ BARU
use App\Models\Tag;
use App\Models\TaskCategory;     // ✅ BARU
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectController extends Controller
{
    protected const TASK_STATUS_COMPLETED = [
        'COMPLETED',
        'FINISHED',
    ];

    public function index()
    {
        $user = Auth::user();

        $projects = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
        ])
            ->visibleFor($user)
            ->orderByDesc('id')
            ->get();

        $statuses = MsProjectStatus::select('id', 'name', 'severity')->get();
        $priorities = MsProjectPriority::select('id', 'name', 'severity')->get();

        $response = [
            'projects' => $projects->toArray(),
            'statuses' => $statuses->toArray(),
            'priorities' => $priorities->toArray(),
        ];

        return Inertia::render('project/Index', Sqids::rec_encode_ids_in_list($response));
    }

    public function show(string $encoded)
    {
        try {
            $projectId = Sqids::decode($encoded);
            $project = Project::with([
                'status:id,name,severity',
                'priority:id,name,severity',
                'projectMembers' => function ($query) {
                    $query->whereHas('user');
                },
                'projectMembers.user:id,name,email',
                'projectMembers.user.media',
                'projectMembers.role:id,name',
                'tasks' => function ($query) {
                    $query->withRecursive();
                },
            ])->findOrFail($projectId);
        } catch (\Exception $e) {
            throw new NotFoundHttpException(404);
        }

        $project->update([
            'progress' => $project->calculateProgress(),
        ]);

        $projectArr = $project->toArray();

        // Get member user IDs - filter out null users
        $memberUserIds = collect($projectArr['project_members'])
            ->filter(function ($member) {
                return isset($member['user']) && ! is_null($member['user']);
            })
            ->pluck('user.id')
            ->filter()
            ->values();

        // Get available users (not members) with avatars
        $availableUsers = User::whereNotIn('id', $memberUserIds)
            ->with('media')
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            })
            ->toArray();

        $roles = MsProjectRole::all(['id', 'name'])->toArray();

        $statuses = MsTaskStatus::select('id', 'name', 'severity', 'score')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();
        $tags = Tag::select('id', 'name', 'severity')->get();

        // Format assignable users with avatar_url - filter out null users
        $assignableUsers = collect($projectArr['project_members'])
            ->filter(function ($member) {
                return isset($member['user']) && ! is_null($member['user']);
            })
            ->map(function ($member) use ($project) {
                $projectMember = $project->projectMembers
                    ->where('user_id', $member['user']['id'])
                    ->first();

                if (! $projectMember || ! $projectMember->user) {
                    return null;
                }

                $user = $projectMember->user;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            })
            ->filter()
            ->unique('id')
            ->values()
            ->toArray();

        // Format members with avatar_url - filter out null users
        $formattedMembers = collect($projectArr['project_members'])
            ->filter(function ($member) {
                return isset($member['user']) && ! is_null($member['user']) && isset($member['role']);
            })
            ->map(function ($member) use ($project) {
                $projectMember = $project->projectMembers
                    ->where('id', $member['id'])
                    ->first();

                if (! $projectMember || ! $projectMember->user) {
                    return null;
                }

                return [
                    'id' => $member['id'],
                    'is_active' => $member['is_active'] ?? true,
                    'user' => [
                        'id' => $projectMember->user->id,
                        'name' => $projectMember->user->name,
                        'email' => $projectMember->user->email,
                        'avatar_url' => $projectMember->user->avatar_url,
                    ],
                    'role' => $member['role'],
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        $projectStatuses = MsProjectStatus::select('id', 'name', 'severity')->get();
        $projectPriorities = MsProjectPriority::select('id', 'name', 'severity')->get();

        // Format tasks with creator information
        $formattedTasks = collect($projectArr['tasks'] ?? [])
            ->map(function ($task) {
                if (isset($task['creator'])) {
                    $creator = User::with('media')->find($task['creator']['id']);
                    if ($creator) {
                        $task['creator']['avatar_url'] = $creator->avatar_url;
                    }
                }

                if (isset($task['sub_task_recursive']) && is_array($task['sub_task_recursive'])) {
                    $task['sub_task_recursive'] = $this->formatSubtasksWithCreator($task['sub_task_recursive']);
                }

                $is_completed = in_array(strtoupper($task['status']['name']), self::TASK_STATUS_COMPLETED);

                $task['completed_at'] = $is_completed ? $task['updated_at'] : null;
                $task['is_overdue'] = $is_completed
                    ? Carbon::parse($task['updated_at'])->isAfter(Carbon::parse($task['due_date'])->endOfDay())
                    : Carbon::parse($task['due_date'])->endOfDay()->isPast();

                return $task;
            })
            ->toArray();

        $sprints = ProjectSprint::with([
            'status:id,name,severity',
            'tasks' => function ($q) {
                $q->with([
                    'status:id,name,severity',
                    'priority:id,name,severity',
                    'type:id,name,severity',
                    'category:id,name,icon,severity',
                    'users:id,name',
                    'users.media',
                ])
                    ->whereNull('parent_id')   // hanya top-level tasks di sprint
                    ->orderBy('id');
            },
        ])
            ->where('project_id', $projectId)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        // Format avatar_url untuk users di dalam sprint tasks
        $formattedSprints = $sprints->map(function ($sprint) {
            $sprintArr = $sprint->toArray();
            $sprintArr['tasks'] = collect($sprintArr['tasks'] ?? [])->map(function ($task) {
                $task['users'] = collect($task['users'] ?? [])->map(function ($user) {
                    $u = User::with('media')->find($user['id']);
                    $user['avatar_url'] = $u?->avatar_url;
                    return $user;
                })->toArray();
                return $task;
            })->toArray();
            return $sprintArr;
        })->toArray();

        // ✅ BARU — Backlog: tasks top-level yang belum masuk sprint manapun
        $backlogTasks = Task::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name',
            'users.media',
        ])
            ->where('project_id', $projectId)
            ->whereNull('parent_id')
            ->doesntHave('sprints')
            ->orderBy('id')
            ->get();

        $formattedBacklog = $backlogTasks->map(function ($task) {
            $arr = $task->toArray();
            $arr['users'] = collect($arr['users'] ?? [])->map(function ($user) {
                $u = User::with('media')->find($user['id']);
                $user['avatar_url'] = $u?->avatar_url;
                return $user;
            })->toArray();
            return $arr;
        })->toArray();

        $taskCategories = TaskCategory::select('id', 'name', 'icon', 'severity')
            ->orderBy('severity')
            ->get()
            ->toArray();

        $epics = Task::whereHas('category', fn($q) => $q->where('name', 'Epic'))
            ->where('project_id', $projectId)
            ->whereNull('parent_id')
            ->get(['id', 'title', 'story_points']);


        $data = [
            'project' => $projectArr,
            'members' => $formattedMembers,
            'roles' => $roles,
            'users' => $availableUsers,
            'tasks' => $formattedTasks,
            'taskStatuses' => $statuses->toArray(),
            'taskPriorities' => $priorities->toArray(),
            'taskTypes' => $types->toArray(),
            'tags' => $tags->toArray(),
            'assignableUsers' => $assignableUsers,
            'statuses' => $projectStatuses->toArray(),
            'priorities' => $projectPriorities->toArray(),
            'sprints' => $formattedSprints,
            'backlog' => $formattedBacklog,
            'taskCategories' => $taskCategories,
            'epics'          => Sqids::rec_encode_ids_in_list($epics->toArray()),
        ];

        return Inertia::render('project/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    /**
     * Helper function to recursively format subtasks with creator information
     */
    private function formatSubtasksWithCreator(array $subtasks): array
    {
        return collect($subtasks)
            ->map(function ($subtask) {
                if (isset($subtask['creator'])) {
                    $creator = User::with('media')->find($subtask['creator']['id']);
                    if ($creator) {
                        $subtask['creator']['avatar_url'] = $creator->avatar_url;
                    }
                }

                if (isset($subtask['sub_task_recursive']) && is_array($subtask['sub_task_recursive'])) {
                    $subtask['sub_task_recursive'] = $this->formatSubtasksWithCreator($subtask['sub_task_recursive']);
                }

                return $subtask;
            })
            ->toArray();
    }

    public function store(ProjectStoreRequest $request)
    {
        $project = Project::create($request->validated());

        $project->update([
            'progress' => $project->calculateProgress(),
        ]);

        $projectId = $project->id;
        $userId = Auth::id();
        $projectRoleId = MsProjectRole::where('name', 'Owner')->first()->id;

        ProjectMember::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'project_role_id' => $projectRoleId,
            'owned_id' => $request['owned_id'],
            'created_by' => $request['created_by'],
            'updated_by' => $request['updated_by'],
            'is_active' => true,
        ]);

        return to_route('project.index')->with('success', 'Project added successfully');
    }

    public function update(ProjectUpdateRequest $request, string $encoded)
    {
        try {
            $id = Sqids::decode($encoded);
            $project = Project::findOrFail($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Project not found.');
        }

        $project->update($request->validated());
        $project->update([
            'progress' => $project->calculateProgress(),
        ]);

        $referer = $request->header('referer');
        $isFromDetail = $referer && str_contains($referer, '/project/' . $encoded);

        if ($isFromDetail) {
            return to_route('project.show', ['encoded' => $encoded]);
        }

        return to_route('project.index');
    }

    public function destroy(string $encoded)
    {
        try {
            $id = Sqids::decode($encoded);
            $project = Project::findOrFail($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Project not found.');
        }

        $project->allTasks()
            ->with('users')
            ->chunkById(100, function ($tasks) {
                foreach ($tasks as $task) {
                    TaskNotification::createTaskNotification(
                        $task,
                        $task->users->pluck('id')->toArray(),
                        TaskNotificationType::DELETED
                    );
                }
            });

        $project->allTasks()->delete();
        $project->delete();

        return to_route('project.index')->with('success', 'Project deleted successfully');
    }
}
