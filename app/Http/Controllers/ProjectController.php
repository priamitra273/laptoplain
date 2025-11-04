<?php

namespace App\Http\Controllers;

use App\Exports\ProjectExport;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Models\Project;
use App\Models\MsProjectStatus;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $service
    ) {}

    public function index()
    {
        // Ambil semua project beserta relasinya
        $projects = Project::with(['status:id,name', 'priority:id,name'])
            ->select([
                'id',
                'emoji',
                'title',
                'description',
                'start_date',
                'due_date',
                'progress',
                'sequence_number',
                'status_id',
                'priority_id',
                'owner_id',
                'owned_id',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id', 'asc')
            ->get();

        // Ambil semua data master untuk dropdown
        $statuses = MsProjectStatus::select('id', 'name')->orderBy('name')->get();
        $priorities = MsProjectPriority::select('id', 'name')->orderBy('name')->get();
        // $roles = MsProjectRole::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('project/Index', [
            'projects' => $projects,
            'statuses' => $statuses,
            'priorities' => $priorities,
            // 'roles' => $roles,
        ]);
    }


    public function store(ProjectStoreRequest $request)
    {
        Project::create($request->validated());
        return to_route('project.index')->with('success', 'Project berhasil dibuat');
    }


    public function update(ProjectStoreRequest $request, Project $project)
    {
        $project->update($request->validated());
        return to_route('project.index')->with('success', 'Project berhasil diperbarui');
    }


    public function destroy(Project $project)
    {
        $project->delete();
        return to_route('project.index')->with('success', 'Project berhasil dihapus');
    }
}
