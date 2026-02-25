<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\TaskNotificationType;
use App\Facades\TaskNotification;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Task;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request)
    {
        $commentableId = Sqids::decode($request->commentable_id);
        $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;

        Comment::create([
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $commentableId,
            'user_id' => Auth::id(),
            'body' => $request->body,
            'parent_id' => $parentId,
            'created_by' => Auth::id(),
            'owned_id' => Auth::id(),
        ]);

        $mentionedUserIds = $request->mentionedUserIds();

        if (!empty($mentionedUserIds)) {
            $task = Task::find($commentableId);

            TaskNotification::createTaskNotification(
                $task,
                $mentionedUserIds,
                TaskNotificationType::MENTIONED
            );
        }

        return back();
    }

    public function update(UpdateCommentRequest $request, string $id)
    {
        $commentId = Sqids::decode($id);
        $comment = Comment::findOrFail($commentId);

        if ($comment->owned_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $newMentionedUserIds = $request->newMentionedUserIds($comment);

        $comment->update([
            'body' => $request->body,
            'updated_by' => Auth::id(),
        ]);

        if (!empty($newMentionedUserIds)) {
            $task = Task::find($comment->commentable_id);

            TaskNotification::createTaskNotification(
                $task,
                $newMentionedUserIds,
                TaskNotificationType::MENTIONED
            );
        }
    }

    public function destroy(string $id)
    {
        $commentId = Sqids::decode($id);
        $comment = Comment::findOrFail($commentId);
        if ($comment->owned_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        $comment->delete();
    }

    public function react(Request $request, string $id)
    {
        $commentId = Sqids::decode($id);
        $comment = Comment::findOrFail($commentId);
        $userId = Auth::id();

        $request->validate([
            'reaction' => 'required|string',
        ]);

        $reactions = $comment->reaction ?? [];

        // Jika user sudah memberi reaksi yang sama, hapus reaksi tersebut
        if (isset($reactions[$userId]) && $reactions[$userId] === $request->reaction) {
            unset($reactions[$userId]);
        } else {
            $reactions[$userId] = $request->reaction;
        }

        $comment->update([
            'reaction' => $reactions,
            'updated_by' => $userId,
        ]);

        // Manual encode keys (user IDs) dari reactions
        $encodedReactions = [];
        foreach ($reactions as $uid => $reactionType) {
            $encodedUserId = Sqids::encode($uid);
            $encodedReactions[$encodedUserId] = $reactionType;
        }

        return response()->json([
            'success' => true,
            'reactions' => $encodedReactions,
        ]);
    }
}
