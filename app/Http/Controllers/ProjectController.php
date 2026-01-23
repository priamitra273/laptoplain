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
use App\Models\ProjectMember;
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
            ->orderByDesc('id')
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

        $project = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'projectMembers.user:id,name,email',
            'projectMembers.user.media', // Load media for avatars
            'projectMembers.role:id,name',
            'tasks' => function ($query) {
                $query->withRecursive();
            },
        ])->find($projectId);

        // If project not found, return 404 page
        if (!$project) {
            return Inertia::render('errors/NotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        $projectArr = $project->toArray();

        // Get member user IDs
        $memberUserIds = collect($projectArr['project_members'])
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

        $statuses = MsTaskStatus::select('id', 'name', 'severity')->get();
        $priorities = MsTaskPriority::select('id', 'name', 'severity')->get();
        $types = MsTaskType::select('id', 'name', 'severity')->get();
        $tags = Tag::select('id', 'name', 'severity')->get();

        // Format assignable users with avatar_url
        $assignableUsers = collect($projectArr['project_members'])
            ->map(function ($member) use ($project) {
                // Get the full user object with media relation
                $user = $project->projectMembers
                    ->where('user.id', $member['user']['id'])
                    ->first()
                    ->user;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();

        // Format members with avatar_url
        $formattedMembers = collect($projectArr['project_members'])
            ->map(function ($member) use ($project) {
                $projectMember = $project->projectMembers
                    ->where('id', $member['id'])
                    ->first();

                return [
                    'id' => $member['id'],
                    'user' => [
                        'id' => $projectMember->user->id,
                        'name' => $projectMember->user->name,
                        'email' => $projectMember->user->email,
                        'avatar_url' => $projectMember->user->avatar_url,
                    ],
                    'role' => $member['role'],
                ];
            })
            ->toArray();

        $currentUser = Auth::user();
        $currentUserId = Auth::id();
        $isAdmin = $currentUser->roles->contains(function ($role) {
            return stripos($role->name, 'admin-') === 0;
        });
        $isPM = $project->projectMembers
            ->where('user.id', $currentUserId)
            ->where('role.name', 'Owner')
            ->isNotEmpty();
        $isMember = $project->projectMembers
            ->where('user.id', $currentUserId)
            ->isNotEmpty();
        $isOwner = $project->projectMembers
            ->where('user.id', $currentUserId)
            ->where('role.name', 'Owner')
            ->isNotEmpty();

        $canManageMembers = $isAdmin || $isPM;

        $data = [
            'project' => $projectArr,
            'members' => $formattedMembers, // Use formatted members with avatar_url
            'roles'   => $roles,
            'users'   => $availableUsers, // Already includes avatar_url
            'tasks'   => $projectArr['tasks'],
            'taskStatuses' => $statuses->toArray(),
            'taskPriorities' => $priorities->toArray(),
            'taskTypes' => $types->toArray(),
            'tags' => $tags->toArray(),
            'assignableUsers' => $assignableUsers, // Already includes avatar_url
            'isAdmin' => $isAdmin,
            'isPM' => $isPM,
            'isMember' => $isMember,
            'isOwner' => $isOwner,
            'canManageMembers' => $canManageMembers
        ];

        return Inertia::render('project/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function store(ProjectStoreRequest $request)
    {
        $project = Project::create($request->validated());

        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        $projectId = $project->id;
        $userId = Auth::id();
        $projectRoleId = MsProjectRole::where('name', 'Owner')->first()->id;

        ProjectMember::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'project_role_id' => $projectRoleId,
            'owned_id' => $request["owned_id"],
            'created_by' => $request["created_by"],
            'updated_by' => $request["updated_by"],
            'is_active' => true
        ]);

        return to_route('project.index')->with('success', 'Project added successfully');
    }

    public function update(ProjectStoreRequest $request, string $encoded)
    {
        $id = Sqids::decode($encoded);

        $project = Project::findOrFail($id);
        $project->update($request->validated());
        $project->update([
            'progress' => $project->calculateProgress()
        ]);

        return to_route('project.index')->with('success', 'Project updated successfully');
    }

    public function destroy(string $encoded)
    {
        $id = Sqids::decode($encoded);

        Project::findOrFail($id)->delete();

        return to_route('project.index')->with('success', 'Project deleted successfully');
    }
}
