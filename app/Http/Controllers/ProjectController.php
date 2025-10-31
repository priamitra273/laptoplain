<?php

namespace App\Http\Controllers;

use App\Exports\ProjectExport;
use App\Http\Requests\Project\ProjectImportRequest;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Imports\ProjectStoreImport;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::select([
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
            ->orderBy('id')
            ->get();

        return Inertia::render('project/Project', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('project/ProjectCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectStoreRequest $request)
    {
        Project::create($request->safe()->toArray());

        return to_route('project.index');
    }

    /**
     * Verify and preview imported file.
     */
    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $result = $this->service->verifyImport($request->get('type'), $request->file('file'));

        return Inertia::render('project/ProjectVerifyImport', [
            'projects' => $result['data'],
            'header' => $result['header'],
        ]);
    }

    /**
     * Import validated data into the database.
     */
    public function import(ProjectImportRequest $request)
    {
        foreach ($request->safe()->projects as $project) {
            Project::create($project);
        }

        return to_route('project.index');
    }

    /**
     * Export all projects to Excel.
     */
    public function export(Request $request)
    {
        $datetime = date('YmdHis');
        return Excel::download(new ProjectExport, "project-$datetime.xlsx");
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return Inertia::render('project/ProjectShow', [
            'project' => $project,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        return Inertia::render('project/ProjectEdit', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectStoreRequest $request, Project $project)
    {
        $project->update($request->safe()->toArray());

        return to_route('project.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return to_route('project.index');
    }
}
