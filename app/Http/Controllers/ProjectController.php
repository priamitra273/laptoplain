<?php

namespace App\Http\Controllers;

use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Http\Requests\Project\ProjectUpdateRequest;
use App\Models\MsProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    /**
     * Show the resources
     */
    public function index(): Response
    {
        $response = $this->projectService->getIndexData();

        return Inertia::render('project/Index', Sqids::rec_encode_ids_in_list($response));
    }

    public function show(string $encoded): Response
    {
        $data = $this->projectService->getShowData($encoded);

        return Inertia::render('project/Detail', Sqids::rec_encode_ids_in_list($data));
    }

    public function store(ProjectStoreRequest $request)
    {
        $project = Project::create($request->validated());

        $project->update([
            'progress' => $project->calculateProgress(),
        ]);

        $projectId = $project->id;
        $userId = Auth::id();
        $projectRoleId = MsProjectRole::where('name', 'Owner')->first()->id;

        ProjectMember::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'project_role_id' => $projectRoleId,
            'owned_id' => $request['owned_id'],
            'created_by' => $request['created_by'],
            'updated_by' => $request['updated_by'],
            'is_active' => true,
        ]);

        return to_route('project.index')->with('success', 'Project added successfully');
    }

    public function update(ProjectUpdateRequest $request, string $encoded)
    {
        try {
            $id = Sqids::decode($encoded);
            $project = Project::findOrFail($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Project not found.');
        }

        $project->update($request->validated());
        $project->update([
            'progress' => $project->calculateProgress(),
        ]);

        $referer = $request->header('referer');
        $isFromDetail = $referer && str_contains($referer, '/project/'.$encoded);

        if ($isFromDetail) {
            return to_route('project.show', ['encoded' => $encoded]);
        }

        return to_route('project.index');
    }

    public function destroy(string $encoded)
    {
        try {
            $id = Sqids::decode($encoded);
            $project = Project::findOrFail($id);
        } catch (\Exception $e) {
            return back()->with('error', 'Project not found.');
        }

        $project->allTasks()
            ->with('users')
            ->chunkById(100, function ($tasks) {
                foreach ($tasks as $task) {
                    TaskNotification::createTaskNotification(
                        $task,
                        $task->users->pluck('id')->toArray(),
                        TaskNotificationType::DELETED
                    );
                }
            });

        $project->allTasks()->delete();
        $project->delete();

        return to_route('project.index')->with('success', 'Project deleted successfully');
    }
}
