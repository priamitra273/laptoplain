<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\Sprint\SprintCompleteRequest;
use App\Http\Requests\Sprint\SprintStartRequest;
use App\Http\Requests\Sprint\SprintStoreRequest;
use App\Http\Requests\Sprint\SprintTaskAssignRequest;
use App\Http\Requests\Sprint\SprintUpdateRequest;
use App\Services\SprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SprintController extends Controller
{
    public function __construct(private SprintService $service) {}

    // GET /project/{projectEncoded}/sprints
    public function index(string $projectEncoded): JsonResponse
    {
        $projectId = Sqids::decode($projectEncoded);
        $data = $this->service->getIndexData($projectId, Auth::user());

        return response()->json(
            Sqids::rec_encode_ids_in_list(['success' => true, ...$data])
        );
    }

    // POST /project/{projectEncoded}/sprints
    public function store(SprintStoreRequest $request, string $projectEncoded): JsonResponse|RedirectResponse
    {
        $sprint = $this->service->store(Sqids::decode($projectEncoded), $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sprint created successfully',
                'sprint' => Sqids::rec_encode_ids_in_list($sprint->toArray()),
            ]);
        }

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Sprint created successfully');
    }

    // PUT /project/{projectEncoded}/sprints/{sprintEncoded}
    public function update(SprintUpdateRequest $request, string $projectEncoded, string $sprintEncoded): RedirectResponse
    {
        $sprint = $this->service->findByProject(Sqids::decode($sprintEncoded), Sqids::decode($projectEncoded));
        $this->service->update($sprint, $request->validated());

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Sprint updated successfully');
    }

    // DELETE /project/{projectEncoded}/sprints/{sprintEncoded}
    public function destroy(Request $request, string $projectEncoded, string $sprintEncoded): JsonResponse|RedirectResponse
    {
        $sprint = $this->service->findByProject(Sqids::decode($sprintEncoded), Sqids::decode($projectEncoded));

        try {
            $this->service->destroy($sprint);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Sprint deleted successfully']);
        }

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Sprint deleted successfully');
    }

    // PATCH /project/{projectEncoded}/sprints/{sprintEncoded}/start
    public function start(SprintStartRequest $request, string $projectEncoded, string $sprintEncoded): RedirectResponse
    {
        $sprint = $this->service->findByProject(Sqids::decode($sprintEncoded), Sqids::decode($projectEncoded));
        $this->service->start($sprint, $request->validated());

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', "Sprint \"{$sprint->name}\" started");
    }

    // PATCH /project/{projectEncoded}/sprints/{sprintEncoded}/complete
    public function complete(SprintCompleteRequest $request, string $projectEncoded, string $sprintEncoded): RedirectResponse
    {
        $sprint = $this->service->findByProject(Sqids::decode($sprintEncoded), Sqids::decode($projectEncoded));
        $this->service->complete($sprint, $request->validated());

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', "Sprint \"{$sprint->name}\" completed");
    }

    // POST /project/{projectEncoded}/sprints/{sprintEncoded}/tasks
    public function assignTask(SprintTaskAssignRequest $request, string $projectEncoded, string $sprintEncoded): JsonResponse|RedirectResponse
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprint = $this->service->findByProject(Sqids::decode($sprintEncoded), $projectId);

        try {
            $this->service->assignTasks($sprint, $request->validated('task_ids'), $projectId);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Task berhasil ditambahkan ke sprint.']);
        }

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Task berhasil ditambahkan ke sprint.');
    }

    // DELETE /project/{projectEncoded}/sprints/{sprintEncoded}/tasks/{taskEncoded}
    public function removeTask(Request $request, string $projectEncoded, string $sprintEncoded, string $taskEncoded): JsonResponse|RedirectResponse
    {
        $sprint = $this->service->findByProject(Sqids::decode($sprintEncoded), Sqids::decode($projectEncoded));
        $this->service->removeTask($sprint, Sqids::decode($taskEncoded));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Task dipindahkan ke backlog.']);
        }

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Task dipindahkan ke backlog.');
    }
}
