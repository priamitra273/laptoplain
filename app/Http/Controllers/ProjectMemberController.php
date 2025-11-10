<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\MsProjectRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Facades\Sqids;

class ProjectMemberController extends Controller
{
    /**
     * Tampilkan daftar member project
     */
    public function show(Request $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $project = Project::findOrFail($projectId);

        // Ambil query params untuk filter
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
            $query->where('project_role_id', $roleId);
        }

        if (!is_null($active)) {
            $query->where('is_active', $active === '1');
        }

        $members = $query->paginate($perPage)->appends($request->query());

        // Tambahkan hashid ke members
        $members->getCollection()->transform(function ($m) {
            $m->hashid = Sqids::encode($m->id);
            return $m;
        });

        // Ambil semua roles dan users
        $roles = MsProjectRole::all(['id', 'name']);
        $users = User::all(['id', 'name']);

        return Inertia::render('project/Members', [
            'project' => [
                'id' => $project->id,
                'hashid' => Sqids::encode($project->id),
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
        ]);
    }

    /**
     * Tambah member ke project
     */
    public function store(Request $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (!$projectId) abort(404);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_role_id' => 'required|exists:ms_project_roles,id',
        ]);

        // Cek apakah user sudah menjadi member
        if (ProjectMember::where('project_id', $projectId)->where('user_id', $request->user_id)->exists()) {
            return redirect()->back()->with('error', 'User already a member');
        }

        ProjectMember::create([
            'project_id' => $projectId,
            'user_id' => $request->user_id,
            'project_role_id' => $request->project_role_id,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Member added');
    }

    /**
     * Update member
     */
    public function update(Request $request, string $encodedProject, string $memberEncoded)
    {
        $projectId = Sqids::decode($encodedProject);
        $memberId = Sqids::decode($memberEncoded);

        if (!$projectId || !$memberId) abort(404);

        $request->validate([
            'project_role_id' => 'required|exists:ms_project_roles,id',
            'is_active' => 'required|boolean',
        ]);

        $member = ProjectMember::where('project_id', $projectId)->findOrFail($memberId);
        $member->update([
            'project_role_id' => $request->project_role_id,
            'is_active' => $request->is_active,
        ]);

        return redirect()->back()->with('success', 'Member updated');
    }

    /**
     * Hapus member
     */
    public function destroy(string $encodedProject, string $memberEncoded)
    {
        $projectId = Sqids::decode($encodedProject);
        $memberId = Sqids::decode($memberEncoded);

        if (!$projectId || !$memberId) abort(404);

        $member = ProjectMember::where('project_id', $projectId)->findOrFail($memberId);
        $member->delete();

        return redirect()->back()->with('success', 'Member removed');
    }
}
