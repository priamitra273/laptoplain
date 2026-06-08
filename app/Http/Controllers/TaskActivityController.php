<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskActivityController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function index(string $encoded): JsonResponse
    {
        try {
            $taskId = Sqids::decode($encoded);
            $task   = Task::findOrFail($taskId);
        } catch (\Exception $e) {
            throw new NotFoundHttpException();
        }

        if (Auth::user()->cannot('view', $task)) {
            abort(403);
        }

        $activities = $this->taskService->getActivities($task, 'updated');

        return response()->json([
            'success'    => true,
            'activities' => $activities,
        ]);
    }
}
