<?php

namespace App\Actions\Task;

use App\Events\TaskCreated;
use App\Models\MsTaskStatus;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    /**
     * Execute the action to create a task.
     */
    public function execute(Project $project, array $data, int $userId): Task
    {
        return DB::transaction(function () use ($project, $data, $userId) {
            $assignUserIds = $this->prepareAssignUserIds($data['assign_users'] ?? [], $userId);
            $tagIds = $this->prepareTagIds($data['add_tag'] ?? []);

            $taskData = $this->prepareTaskData($project, $data, $userId);
            $task = Task::create($taskData);

            $this->syncRelationships($task, $project, $assignUserIds, $tagIds, $data['sprint_id'] ?? null);

            event(new TaskCreated($task, $assignUserIds));

            return $task;
        });
    }

    /**
     * Prepare assigned user IDs, ensuring the creator is included if others are assigned.
     */
    private function prepareAssignUserIds(array $assignUserIds, int $userId): array
    {
        if (! empty($assignUserIds) && ! in_array($userId, $assignUserIds)) {
            $assignUserIds[] = $userId;
        }

        return $assignUserIds;
    }

    /**
     * Process tags, creating new ones if necessary and returning all tag IDs.
     */
    private function prepareTagIds(array $tagData): array
    {
        $tagIds = $tagData['exists'] ?? [];

        foreach ($tagData['new'] ?? [] as $newTag) {
            $tag = Tag::create([
                'name' => $newTag['name'],
                'severity' => $newTag['severity'],
            ]);

            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    /**
     * Prepare the task data for creation.
     */
    private function prepareTaskData(Project $project, array $data, int $userId): array
    {
        $taskData = $data;
        $taskData['project_id'] = $project->id;
        $taskData['parent_id'] = $data['parent_id'] ?? null;
        $taskData['created_by'] = $userId;
        $taskData['progress'] = $this->calculateInitialProgress($data['status_id'] ?? null);

        $taskData['sequence_number'] ??= Task::where('project_id', $project->id)->max('sequence_number') + 1;

        // Remove non-model attributes
        unset($taskData['assign_users'], $taskData['add_tag'], $taskData['sprint_id']);

        return $taskData;
    }

    /**
     * Calculate initial progress based on the status.
     */
    private function calculateInitialProgress(?int $statusId): float
    {
        if (! $statusId) {
            return 0;
        }

        $taskStatus = MsTaskStatus::find($statusId);

        return $taskStatus ? (float) $taskStatus->score : 0;
    }

    /**
     * Sync task relationships (sprints, users, and tags).
     */
    private function syncRelationships(Task $task, Project $project, array $assignUserIds, array $tagIds, ?int $sprintId): void
    {
        if ($sprintId) {
            $sprint = ProjectSprint::where('project_id', $project->id)->find($sprintId);
            if ($sprint) {
                $sprint->tasks()->syncWithoutDetaching([$task->id]);
            }
        }

        if (! empty($assignUserIds)) {
            $task->users()->syncWithoutDetaching($assignUserIds);
        }

        if (! empty($tagIds)) {
            $task->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
