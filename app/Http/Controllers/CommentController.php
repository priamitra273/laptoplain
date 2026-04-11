<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class CommentController extends Controller
{
    public function __construct(protected CommentService $commentService)
    {
    }

    public function store(StoreCommentRequest $request)
    {
        $this->commentService->createComment(
            $request->validated(),
            $request->mentionedUserIds()
        );

        return back();
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        Gate::authorize('update', $comment);

        $this->commentService->updateComment(
            $comment,
            $request->validated(),
            $request->newMentionedUserIds($comment)
        );

        return back();
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return back();
    }

    public function react(Request $request, Comment $comment)
    {
        $request->validate([
            'reaction' => 'required|string',
        ]);

        $reactions = $this->commentService->toggleReaction(
            $comment,
            $request->get('reaction')
        );

        return response()->json([
            'success' => true,
            'reactions' => $reactions,
        ]);
    }
}

