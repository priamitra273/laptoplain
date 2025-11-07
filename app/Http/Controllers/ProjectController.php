<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Models\Project;
use App\Models\MsProjectStatus;
use App\Models\MsProjectPriority;
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
            
        $projects->transform(function ($p) {
            $p->encoded = Sqids::encode($p->id);
            return $p;
        });

        return Inertia::render('project/Index', [
            'projects'   => $projects,
            'statuses'   => MsProjectStatus::select('id', 'name', 'severity')->get(),
            'priorities' => MsProjectPriority::select('id', 'name', 'severity')->get(),
        ]);
    }

    public function show(string $encoded)
    {
        $id = Sqids::decode($encoded);

        $project = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'projectMembers.user:id,name,email',
            'projectMembers.role:id,name',
        ])->findOrFail($id);

        $project->encoded = Sqids::encode($project->id);

        return Inertia::render('project/Detail', [
            'project' => $project,
        ]);
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
