<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Models\Project;
use App\Models\MsProjectStatus;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity'
        ])
            ->orderBy('id')
            ->get();

        $statuses = MsProjectStatus::select('id', 'name', 'severity')->get();
        $priorities = MsProjectPriority::select('id', 'name', 'severity')->get();

        $response = [
            'projects'   => $projects->toArray(),
            'statuses'   => $statuses->toArray(),
            'priorities' => $priorities->toArray(),
        ];

        return Inertia::render('project/Index', Sqids::rec_encode_ids_in_list($response));
    }

    public function show(string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $project = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'projectMembers.user:id,name,email',
            'projectMembers.role:id,name',
            'tasks' => function ($query) {
                $query->withRecursive();
            },
        ])->findOrFail($projectId);

        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        $projectArr = $project->toArray();

        $memberUserIds = collect($projectArr['project_members'])
            ->pluck('user.id')
            ->filter()
            ->values();

        $availableUsers = User::whereNotIn('id', $memberUserIds)
            ->get(['id', 'name'])
            ->toArray();

        $roles = MsProjectRole::all(['id', 'name'])->toArray();

        $statuses = MsTaskStatus::select('id', 'name', 'severity')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();
        $tags = Tag::select('id', 'name', 'severity')->get();

        $assignableUsers = collect($projectArr['project_members'])
            ->pluck('user')
            ->unique('id')
            ->values();

        $currentUser = Auth::user();

        $isAdmin = $currentUser->hasAnyRole(['admin-it', 'Admin', 'admin', 'Administrator', 'administrator']);
        $isPM = $project->projectMembers
            ->where('user.id', Auth::id())
            ->where('role.name', 'Project Manager')
            ->isNotEmpty();


        $canManageMembers = $isAdmin || $isPM;

        $data = [
            'project' => $projectArr,
            'members' => $projectArr['project_members'],
            'roles'   => $roles,
            'users'   => $availableUsers,
            'tasks'   => $projectArr['tasks'],
            'taskStatuses' => $statuses->toArray(),
            'taskPriorities' => $priorities->toArray(),
            'taskTypes' => $types->toArray(),
            'tags' => $tags->toArray(),
            'assignableUsers' => $assignableUsers->toArray(),
            'isAdmin' => $isAdmin,
            'isPM' => $isPM,
            'canManageMembers' => $canManageMembers
        ];

        return Inertia::render('project/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function store(ProjectStoreRequest $request)
    {
        $project = Project::create($request->validated());

        // Set progress default (0)
        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        return to_route('project.index');
    }

    public function update(ProjectStoreRequest $request, string $encoded)
    {
        $id = Sqids::decode($encoded);

        $project = Project::findOrFail($id);
        $project->update($request->validated());

        // Update progress terbaru setelah update data project
        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        return to_route('project.index');
    }

    public function destroy(string $encoded)
    {
        $id = Sqids::decode($encoded);

        Project::findOrFail($id)->delete();

        return to_route('project.index');
    }
}
