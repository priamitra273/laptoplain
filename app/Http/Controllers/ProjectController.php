<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Models\Project;
use App\Models\MsProjectStatus;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\User;
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
        ])->findOrFail($projectId)
            ->toArray();

        $memberUserIds = collect($project['project_members'])
            ->pluck('user.id')
            ->filter()
            ->values();

        $availableUsers = User::whereNotIn('id', $memberUserIds)
            ->get(['id', 'name'])
            ->toArray();

        $roles = MsProjectRole::all(['id', 'name'])->toArray();

        $data = [
            'project' => $project,
            'members' => $project['project_members'],
            'roles'   => $roles,
            'users'   => $availableUsers,
        ];

        return Inertia::render('project/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function store(ProjectStoreRequest $request)
    {
        Project::create($request->validated());
        return to_route('project.index');
    }

    public function update(ProjectStoreRequest $request, string $encoded)
    {
        $id = Sqids::decode($encoded);

        $project = Project::findOrFail($id);
        $project->update($request->validated());

        return to_route('project.index');
    }

    public function destroy(string $encoded)
    {
        $id = Sqids::decode($encoded);

        Project::findOrFail($id)->delete();

        return to_route('project.index');
    }
}
