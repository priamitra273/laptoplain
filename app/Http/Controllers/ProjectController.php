<?php

namespace App\Http\Controllers;

use App\Data\Project\ProjectFiltersData;
use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Http\Requests\Project\ProjectUpdateRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    /**
     * Show the resources
     */
    public function index(Request $request): Response
    {
        $filters = ProjectFiltersData::fromRequest($request);
        $response = $this->projectService->getIndexData($filters);

        return Inertia::render('favorites/project/Index', Sqids::rec_encode_ids_in_list($response));
    }

    public function show(string $encoded): RedirectResponse
    {
        return to_route('project.show.kanban', ['encoded' => $encoded]);
    }

    public function store(ProjectStoreRequest $request)
    {
        $this->projectService->createProject($request->validated());

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

        $this->projectService->updateProject($project, $request->validated());

        $referer = $request->header('referer');
        $isFromDetail = $referer && str_contains($referer, '/project/'.$encoded);

        if ($isFromDetail) {
            return back();
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
