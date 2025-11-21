<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\ProjectMember;
use App\Models\TaskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskUserController extends Controller
{
    public function assign(Request $request, string $projectEncoded, string $taskEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $taskId = Sqids::decode($taskEncoded);

        if (!$projectId || !$taskId) abort(404);

        // ✅ CEK APAKAH USER LOGIN ADALAH PROJECT MANAGER
        $isPM = ProjectMember::where('project_id', $projectId)
            ->where('user_id', Auth::id())
            ->whereHas('role', function ($q) {
                $q->where('name', 'Project Manager');
            })
            ->exists();

        if (!$isPM) {
            return back()->with('error', 'You do not have permission to assign user to task.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $taskUser = TaskUser::where('task_id', $taskId)
            ->where('user_id', $request->user_id)
            ->first();

        if ($taskUser) {
            $taskUser->update([
                'updated_by' => Auth::id(),
            ]);
        } else {
            TaskUser::create([
                'task_id'    => $taskId,
                'user_id'    => $request->user_id,
                'owned_id'   => Auth::id(),
                'created_by' => Auth::id(),
            ]);
        }


        return back()->with('success', 'User assigned');
    }

    public function unassign(Request $request, string $projectEncoded, string $taskEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $taskId = Sqids::decode($taskEncoded);

        if (!$projectId || !$taskId) abort(404);

        // CEK PM
        $isPM = ProjectMember::where('project_id', $projectId)
            ->where('user_id', Auth::id())
            ->whereHas('role', function ($q) {
                $q->where('name', 'Project Manager');
            })
            ->exists();

        if (!$isPM) {
            return back()->with('error', 'You do not have permission to remove user from task.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        TaskUser::where('task_id', $taskId)
            ->where('user_id', $request->user_id)
            ->delete();

        return back()->with('success', 'User removed');
    }
}
