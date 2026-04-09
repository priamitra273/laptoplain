<?php

namespace App\Actions\Task;

use App\Enums\TaskNotificationType;
use App\Facades\TaskNotification;
use App\Models\MsTaskStatus;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateTaskAction
{
    /**
     * Execute the action to create a task.
     *
     * @param  Project  $project
     * @param  array  $data
     * @param  int  $userId
     * @return Task
     */
    public function execute(Project $project, array $data, int $userId): Task
    {
        return DB::transaction(function () use ($project, $data, $userId) {
            $data['project_id'] = $project->id;
            $data['parent_id'] = $data['parent_id'] ?? null;
            $data['created_by'] = $userId;

            $assignUserIds = $data['assign_users'] ?? [];
            $sprintId = $data['sprint_id'] ?? null;

            // Auto-assign current user if not in list
            if (! empty($assignUserIds) && ! in_array($userId, $assignUserIds)) {
                $assignUserIds[] = $userId;
            }

            $addTagExist = $data['add_tag']['exists'] ?? [];
            $addTagNew = [];

            foreach ($data['add_tag']['new'] ?? [] as $newTag) {
                $tag = Tag::create([
                    'name' => $newTag['name'],
                    'severity' => $newTag['severity'],
                ]);

                $addTagNew[] = $tag->id;
            }

            // Set default progress based on status
            if (isset($data['status_id'])) {
                $taskStatus = MsTaskStatus::find($data['status_id']);
                $progress = $taskStatus ? $taskStatus->score : 0;
            } else {
                $progress = 0;
            }

            $data['progress'] = $progress;

            unset($data['assign_users'], $data['add_tag'], $data['sprint_id']);

            $task = Task::create($data);

            if ($sprintId) {
                $sprint = ProjectSprint::where('project_id', $project->id)->find($sprintId);
                if ($sprint) {
                    $sprint->tasks()->syncWithoutDetaching([$task->id]);
                }
            }

            if (! empty($assignUserIds)) {
                $task->users()->syncWithoutDetaching($assignUserIds);
            }

            if (! empty($addTagExist)) {
                $task->tags()->syncWithoutDetaching($addTagExist);
            }

            if (! empty($addTagNew)) {
                $task->tags()->syncWithoutDetaching($addTagNew);
            }

            // Recursive progress update for parents
            $parent = $task->parent;
            while ($parent) {
                $parent->update([
                    'progress' => $parent->calculateProgress(),
                ]);

                $parent = $parent->parent;
            }

            if (! empty($assignUserIds)) {
                try {
                    TaskNotification::createTaskNotification(
                        $task,
                        $assignUserIds,
                        TaskNotificationType::CREATED
                    );
                } catch (Throwable $th) {
                    // Fail silently for notification errors
                }
            }

            return $task;
        });
    }
}
