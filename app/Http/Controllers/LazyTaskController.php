<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\RecalculateProgressAction;
use App\Actions\Task\UpdateTaskAction;
use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Models\Task;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LazyTaskController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    public function store(
        TaskStoreRequest $request,
        string $encoded,
        CreateTaskAction $createTaskAction,
        RecalculateProgressAction $recalculateProgressAction
    ): JsonResponse {
        $project = $this->projectService->findByEncodedId($encoded);

        abort_if($request->user()->cannot('create', [Task::class, $project]), 403);

        $task = $createTaskAction->execute($project, $request->validated(), Auth::id());

        $recalculateProgressAction->execute($task);

        return response()->json([
            'success' => true,
            'id' => Sqids::encode($task->id),
        ]);
    }

    public function update(
        TaskUpdateRequest $request,
        string $encoded,
        string $taskEncoded,
        UpdateTaskAction $updateTaskAction
    ): JsonResponse {
        $taskId = Sqids::decode($taskEncoded);

        $task = Task::query()->findOrFail($taskId);

        $project = $this->projectService->findByEncodedId($encoded);
        abort_if($project->id !== $task->project_id, 404);
        abort_if($request->user()->cannot('update', $task), 403);

        $updateTaskAction->execute($task, $request->validated());

        return response()->json(['success' => true]);
    }
}
