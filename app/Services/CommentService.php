<?php

namespace App\Services;

use App\Enums\TaskNotificationType;
use App\Facades\Sqids;
use App\Facades\TaskNotification;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    /**
     * Create a new comment and handle notifications.
     */
    public function createComment(array $data, array $mentionedUserIds): Comment
    {
        $commentableId = Sqids::decode($data['commentable_id']);
        $parentId = isset($data['parent_id']) ? Sqids::decode($data['parent_id']) : null;

        $comment = Comment::create([
            'commentable_type' => $data['commentable_type'],
            'commentable_id' => $commentableId,
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'parent_id' => $parentId,
            'created_by' => Auth::id(),
            'owned_id' => Auth::id(),
        ]);

        if (! empty($mentionedUserIds)) {
            $task = Task::find($commentableId);

            TaskNotification::createTaskNotification(
                $task,
                $mentionedUserIds,
                TaskNotificationType::MENTIONED
            );
        }

        return $comment;
    }

    /**
     * Update an existing comment and handle new mentions.
     */
    public function updateComment(Comment $comment, array $data, array $newMentionedUserIds): Comment
    {
        $comment->update([
            'body' => $data['body'],
            'updated_by' => Auth::id(),
        ]);

        if (! empty($newMentionedUserIds)) {
            $task = Task::find($comment->commentable_id);

            TaskNotification::createTaskNotification(
                $task,
                $newMentionedUserIds,
                TaskNotificationType::MENTIONED
            );
        }

        return $comment;
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Comment $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Toggle a reaction on a comment.
     */
    public function toggleReaction(Comment $comment, string $reactionType): void
    {
        $userId = Auth::id();

        $existingReaction = $comment->reactions()->where('user_id', $userId)->where('reaction', $reactionType)->first();

        // If user already gave the same reaction, remove it
        if ($existingReaction) {
            $existingReaction->delete();
        } else {
            $comment->reactions()->updateOrCreate(
                ['user_id' => $userId],
                ['reaction' => $reactionType]
            );
        }
    }

    public function loadRelations(Comment $comment): Comment
    {
        return $comment->load([
            'user',
            'replies' => fn ($q) => $q->orderBy('id', 'asc'),
            'replies.user',
            'replies.reaction_group_count',
            'reaction_group_count',
        ]);
    }
}
