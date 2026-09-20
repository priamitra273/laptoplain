<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\ProjectRepository;
use App\Services\ProjectLazyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lazy, per-tab project detail (resources/js/pages/project-lazy/*).
 *
 * Each tab method returns the cheap persistent-shell props + ONLY that tab's data.
 * Heavy/edit-time data is fetched on-demand via the JSON endpoints below.
 */
class ProjectTabController extends Controller
{
    public function __construct(
        private ProjectLazyService $service,
        private ProjectRepository $repository,
    ) {}

    public function kanban(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/kanban/Kanban', $project, $this->service->kanbanData($project));
    }

    public function list(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/task/List', $project, $this->service->listData($project));
    }

    public function backlog(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/backlog/Backlog', $project, $this->service->backlogData($project));
    }

    public function detail(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/detail/Detail', $project, $this->service->detailData($project));
    }

    public function team(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/team/Team', $project, $this->service->teamData($project));
    }

    public function timeline(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/timeline/Timeline', $project, $this->service->timelineData($project));
    }

    public function report(string $encoded): Response
    {
        $project = $this->repository->findShell($encoded);

        return $this->renderTab('favorites/project/detail/report/Report', $project, []);
    }

    /**
     * On-demand: full task payload for the edit form.
     */
    public function taskEdit(Request $request, string $projectEncoded, Task $task): JsonResponse
    {
        abort_if($request->user()->cannot('view', $task), 403);

        $task = $this->repository->getTaskForEdit($task->id);

        return response()->json([
            'data' => Sqids::rec_encode_ids_in_list($this->service->taskEditData($task)),
        ]);
    }

    /**
     * On-demand: flat parent-task picker options for the form.
     */
    public function taskParentOptions(string $projectEncoded): JsonResponse
    {
        $projectId = Sqids::decodeOrFail($projectEncoded);

        return response()->json([
            'data' => Sqids::rec_encode_ids_in_list($this->service->taskParentOptions($projectId)),
        ]);
    }

    /**
     * @param  array<string, mixed>  $tabData
     */
    private function renderTab(string $component, Project $project, array $tabData): Response
    {
        $shell = $this->service->shellData($project);

        $data = array_merge($shell, $tabData);

        return Inertia::render($component, Sqids::rec_encode_ids_in_list($data));
    }
}
