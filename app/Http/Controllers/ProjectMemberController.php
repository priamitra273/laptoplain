<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\MsProjectRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Facades\Sqids;
use App\Http\Requests\ProjectMember\StoreProjectMemberRequest;
use App\Http\Requests\ProjectMember\UpdateProjectMemberRequest;

class ProjectMemberController extends Controller
{
    public function members(Request $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $project = Project::findOrFail($projectId);

        $search = $request->query('search', '');
        $roleId = $request->query('role_id', null);
        $active = $request->query('active', null);
        $perPage = (int) $request->query('per_page', 10);

        // Query anggota project dengan filter
        $query = ProjectMember::with(['user', 'role'])
            ->where('project_id', $project->id);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($roleId) {
            $query->where('project_role_id', Sqids::decode($roleId));
        }

        if (!is_null($active)) {
            $query->where('is_active', $active === '1');
        }

        $members = $query->paginate($perPage)->toArray();
        $memberUserIds = collect($members['data'])
            ->pluck('user.id')
            ->filter()
            ->values();

        $roles = MsProjectRole::all(['id', 'name'])->toArray();
        $users = User::whereNotIn('id', $memberUserIds)->get(['id', 'name'])->toArray();

        $response = [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
            ],
            'members' => $members,
            'roles' => $roles,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role_id' => $roleId,
                'active' => $active,
                'per_page' => $perPage,
            ],
        ];
        $responseEncoded = Sqids::rec_encode_ids_in_list($response);

        return Inertia::render('project/Members', $responseEncoded);
    }

    public function store(StoreProjectMemberRequest $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);
        
        $validated = $request->validated();
        $user_id = $validated['user_id'];

        if (ProjectMember::where('project_id', $projectId)->where('user_id', $user_id)->exists()) {
            return redirect()->back()->with('error', 'User already a member');
        }

        $validated['project_id'] = $projectId;
        $validated['is_active'] = true;

        ProjectMember::create($validated);

        return to_route('project.members.members', ['encoded' => $encoded])
            ->with('success', 'Member added successfully');
    }

    public function update(UpdateProjectMemberRequest $request, string $encoded, string $memberEncoded)
    {
        $id = Sqids::decode($memberEncoded);

        $member = ProjectMember::findOrFail($id);
        $member->update($request->validated());

        return to_route('project.members.members', ['encoded' => $encoded])
            ->with('success', 'Member updated successfully');
    }

    public function destroy(string $encoded, string $memberEncoded)
    {
        $id = Sqids::decode($memberEncoded);

        ProjectMember::findOrFail($id)->delete();

        return to_route('project.members.members', ['encoded' => $encoded])
            ->with('success', 'Member deleted successfully');
    }
}
