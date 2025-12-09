<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'body' => 'required|string',
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|string',
            'parent_id' => 'nullable|string',
        ]);

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

        return back();
    }

    public function update(Request $request, string $id)
    {
        $commentId = Sqids::decode($id);
        $request->validate([
            'body' => 'required|string',
        ]);

        $comment = Comment::findOrFail($commentId);
        if ($comment->owned_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $comment->update([
            'body' => $request->body,
            'updated_by' => Auth::id(),
        ]);
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

        return back();
    }
}
