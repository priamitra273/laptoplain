<?php

namespace App\Actions\Task;

use App\Enums\TaskNotificationType;
use App\Facades\TaskNotification;
use App\Models\MsTaskStatus;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    /**
     * Execute the action to update a task.
     *
     * @param  array<string, mixed>  $data  Validated data dari TaskUpdateRequest
     */
    public function execute(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $data = $this->handleStatusChange($task, $data);
            $data = $this->handleProgressFromStatus($task, $data);

            $this->syncTags($task, $data);

            $assignUserIds = $data['assign_users'] ?? [];
            $unassignUserIds = $data['unassign_users'] ?? [];

            $data = $this->cleanNonModelAttributes($data);

            // Jika task punya children, progress tidak boleh diubah manual
            if ($task->children()->exists() && isset($data['progress'])) {
                unset($data['progress']);
            }

            $task->update($data);

            $this->syncMedia($task, $data);

            $allUserIds = $this->syncUsers($task, $assignUserIds, $unassignUserIds);
            $this->recalculateParentProgress($task, $data);
            $this->sendNotification($task, $allUserIds);

            return $task->fresh();
        });
    }

    /**
     * Handle status change — set completed_at and progress if status changed to Completed.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleStatusChange(Task $task, array $data): array
    {
        if (! isset($data['status_id']) || $data['status_id'] === $task->status_id) {
            return $data;
        }

        $completedStatusId = MsTaskStatus::where('name', 'Completed')->value('id');

        if ((int) $data['status_id'] === (int) $completedStatusId) {
            $data['completed_at'] = now();

            if (! $task->children()->exists()) {
                $data['progress'] = 100;
            }
        } else {
            $data['completed_at'] = null;
        }

        return $data;
    }

    /**
     * If status changed (not completed), set progress according to score from MsTaskStatus.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleProgressFromStatus(Task $task, array $data): array
    {
        if (
            isset($data['status_id']) &&
            $data['status_id'] !== $task->status_id &&
            ! isset($data['completed_at'])
        ) {
            $taskStatus = MsTaskStatus::find($data['status_id']);
            $data['progress'] = $taskStatus ? $taskStatus->score : 0;
        }

        return $data;
    }

    /**
     * Sync tags — attach existing, create new, detach removed.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncTags(Task $task, array $data): void
    {
        // Attach existing tags
        if (! empty($data['add_tag']['exists'])) {
            $task->tags()->syncWithoutDetaching($data['add_tag']['exists']);
        }

        // Create and attach new tags
        $newTagIds = [];
        foreach ($data['add_tag']['new'] ?? [] as $newTag) {
            $tag = Tag::create([
                'name' => $newTag['name'],
                'severity' => $newTag['severity'],
            ]);
            $newTagIds[] = $tag->id;
        }

        if (! empty($newTagIds)) {
            $task->tags()->syncWithoutDetaching($newTagIds);
        }

        // Detach removed tags
        if (! empty($data['remove_tag'])) {
            $task->tags()->detach($data['remove_tag']);
        }
    }

    /**
     * Sync user assignments — assign new users and unassign removed users.
     *
     * @param  array<int>  $assignUserIds
     * @param  array<int>  $unassignUserIds
     * @return array<int> All user IDs (existing + newly assigned)
     */
    private function syncUsers(Task $task, array $assignUserIds, array $unassignUserIds): array
    {
        $existingUserIds = $task->users()
            ->whereNotNull('users.id')
            ->pluck('users.id')
            ->toArray();

        $allUserIds = array_merge($existingUserIds, $assignUserIds);

        foreach ($assignUserIds as $userId) {
            $task->assignUser($userId);
        }

        if (! empty($unassignUserIds)) {
            $task->users()->detach($unassignUserIds);
        }

        return $allUserIds;
    }

    /**
     * Recalculate progress for all parent tasks recursively.
     *
     * @param  array<string, mixed>  $data
     */
    private function recalculateParentProgress(Task $task, array $data): void
    {
        $hasChildren = $task->children()->exists();

        if (! $hasChildren && isset($data['progress'])) {
            $parent = $task->parent;
            while ($parent) {
                $parent->update(['progress' => $parent->calculateProgress()]);
                $parent = $parent->parent;
            }
        }
    }

    /**
     * Send task updated notification.
     *
     * @param  array<int>  $allUserIds
     */
    private function sendNotification(Task $task, array $allUserIds): void
    {
        try {
            TaskNotification::createTaskNotification(
                $task,
                $allUserIds,
                TaskNotificationType::UPDATED
            );
        } catch (\Throwable $th) {
            // Silently fail — notification tidak boleh menggagalkan update
        }
    }

    /**
     * Remove non-model attributes from data before update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function cleanNonModelAttributes(array $data): array
    {
        unset(
            $data['assign_users'],
            $data['unassign_users'],
            $data['add_tag'],
            $data['remove_tag'],
        );

        return $data;
    }

    /**
     * Sync media — attach existing, create new, detach removed.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncMedia(Task $task, array $data): void
    {
        if (! empty($data['attachments'])) {
            $existingMediaUuids = array_map(
                fn ($media) => $media['uuid'],
                array_filter($data['attachments'], fn ($attachment) => is_array($attachment))
            );

            $task->media()->whereNotIn('uuid', $existingMediaUuids)->get()->each->delete();
        } else {
            $task->clearMediaCollection('attachments');
        }

        if (request()->hasFile('attachments')) {
            $task->addMediaFromRequest('attachments')->toMediaCollection('attachments');
        }
    }
}
