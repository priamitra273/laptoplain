<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\ProjectMember\StoreProjectMemberRequest;
use App\Http\Requests\ProjectMember\UpdateProjectMemberRequest;
use App\Models\MsProjectRole;
use App\Models\ProjectMember;

class ProjectMemberController extends Controller
{
    public function store(StoreProjectMemberRequest $request, string $encoded)
    {
        $projectId = Sqids::decode($encoded);
        if (! $projectId) {
            abort(404);
        }

        $validated = $request->validated();
        $user_id = $validated['user_id'];

        if (ProjectMember::where('project_id', $projectId)->where('user_id', $user_id)->exists()) {
            return redirect()->back()->with('error', 'User already a member');
        }

        $validated['project_id'] = $projectId;
        $validated['is_active'] = true;

        ProjectMember::create($validated);

        return to_route('project.show.team', ['encoded' => $encoded])
            ->with('success', 'Member added successfully');
    }

    public function update(UpdateProjectMemberRequest $request, string $encoded, string $memberEncoded)
    {
        $memberId = Sqids::decode($memberEncoded);
        $member = ProjectMember::findOrFail($memberId);

        $ownerRoleId = MsProjectRole::where('name', 'Owner')->value('id');

        $isCurrentlyOwner = $member->project_role_id === $ownerRoleId;
        $willBeOwner = (int) $request->project_role_id === $ownerRoleId;

        if ($isCurrentlyOwner && ! $willBeOwner) {

            $ownerCount = ProjectMember::where('project_id', $member->project_id)
                ->where('project_role_id', $ownerRoleId)
                ->count();

            if ($ownerCount <= 1) {
                return back()->withErrors([
                    'project_role_id' => 'Project must have at least one Owner.',
                ]);
            }
        }

        $member->update($request->validated());

        return to_route('project.show.team', ['encoded' => $encoded])
            ->with('success', 'Member updated successfully');
    }

    public function destroy(string $encoded, string $memberEncoded)
    {
        $memberId = Sqids::decode($memberEncoded);
        $member = ProjectMember::findOrFail($memberId);

        $ownerRoleId = MsProjectRole::where('name', 'Owner')->value('id');

        if ($member->project_role_id === $ownerRoleId) {

            $ownerCount = ProjectMember::where('project_id', $member->project_id)
                ->where('project_role_id', $ownerRoleId)
                ->count();

            if ($ownerCount <= 1) {
                return back()->withErrors([
                    'member' => 'Project must have at least one Owner.',
                ]);
            }
        }

        $member->delete();

        return to_route('project.show.team', ['encoded' => $encoded])
            ->with('success', 'Member deleted successfully');
    }
}
