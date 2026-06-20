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
        UpdateTaskAction $updateTaskAction,
        RecalculateProgressAction $recalculateProgressAction
    ): JsonResponse {
        $taskId = Sqids::decode($taskEncoded);

        $task = Task::query()->findOrFail($taskId);

        $project = $this->projectService->findByEncodedId($encoded);
        abort_if($project->id !== $task->project_id, 404);
        abort_if($request->user()->cannot('update', $task), 403);

        $oldParentId = $task->parent_id;

        $task = $updateTaskAction->execute($task, $request->validated());

        if ($task->parent_id !== $oldParentId) {
            $this->recomputeChangedParent($task->parent, $recalculateProgressAction);
            $this->recomputeChangedParent(
                $oldParentId !== null ? Task::query()->find($oldParentId) : null,
                $recalculateProgressAction
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Recompute a parent whose child set changed during a re-parent, then its ancestors and the project.
     * A parent that became childless falls back to its own status score (mirrors the frontend leaf rule).
     */
    private function recomputeChangedParent(?Task $parent, RecalculateProgressAction $recalculateProgressAction): void
    {
        if ($parent === null) {
            return;
        }

        $progress = $parent->children()->exists()
            ? $parent->calculateProgress()
            : (float) ($parent->status?->score ?? 0);

        $parent->update(['progress' => $progress]);

        $recalculateProgressAction->execute($parent);
    }
}
