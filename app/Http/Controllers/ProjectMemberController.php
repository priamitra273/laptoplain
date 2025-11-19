<?php

namespace App\Http\Controllers;

use App\Models\ProjectMember;

use App\Facades\Sqids;
use App\Http\Requests\ProjectMember\StoreProjectMemberRequest;
use App\Http\Requests\ProjectMember\UpdateProjectMemberRequest;

class ProjectMemberController extends Controller
{
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

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Member added successfully');
    }

    public function update(UpdateProjectMemberRequest $request, string $encoded, string $memberEncoded)
    {
        $id = Sqids::decode($memberEncoded);

        $member = ProjectMember::findOrFail($id);
        $member->update($request->validated());

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Member updated successfully');
    }

    public function destroy(string $encoded, string $memberEncoded)
    {
        $id = Sqids::decode($memberEncoded);

        ProjectMember::findOrFail($id)->delete();

        return to_route('project.show', ['encoded' => $encoded])
            ->with('success', 'Member deleted successfully');
    }
}
