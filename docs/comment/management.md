# Comment Management

Sistem komentar pada task (polymorphic) dengan dukungan @mentions dan reactions (toggle).

---

## 1. Create Comment

```mermaid
sequenceDiagram
    Browser->>Backend: POST /comments
    Note over Backend: Validasi StoreCommentRequest:<br/>body=required|string<br/>commentable_type=required|string<br/>commentable_id=required|string<br/>parent_id=nullable|string<br/>mentionedUserIds(): extract @mentions via regex + Sqids decode

    Backend->>CommentService: createComment(validated, mentionedUserIds)
    CommentService->>CommentService: Sqids::decode commentable_id + parent_id
    CommentService->>DB: Comment::create({body, commentable, parent_id, user_id})
    alt Ada mentioned users
        CommentService->>DB: Cari task terkait komentar
        CommentService->>DB: TaskNotification::createTaskNotification(task, mentionedUserIds, MENTIONED)
        Note over DB: Notifikasi ditulis ke pivot table + Redis cache notif:user:{id}
    end
    Backend->>Browser: Redirect back
```

## 2. Update Comment

```mermaid
sequenceDiagram
    Browser->>Backend: PUT /comments/{comment}
    Note over Backend: Validasi UpdateCommentRequest:<br/>body=required|string<br/>newMentionedUserIds(): diff old vs new mentions

    Backend->>Backend: Gate::authorize('update', comment)
    Backend->>CommentService: updateComment(comment, validated, newMentionedUserIds)
    CommentService->>DB: Update body + updated_by
    alt New mentions
        CommentService->>DB: TaskNotification create (MENTIONED)
    end
    Backend->>Browser: Redirect back
```

## 3. Delete Comment

```mermaid
sequenceDiagram
    Browser->>Backend: DELETE /comments/{comment}
    Backend->>Backend: Gate::authorize('delete', comment)
    Backend->>CommentService: deleteComment(comment)
    CommentService->>DB: $comment->delete()
    Backend->>Browser: Redirect back
```

## 4. Toggle Reaction

```mermaid
sequenceDiagram
    Browser->>Backend: POST /comments/{comment}/reaction
    Note over Backend: Validasi reaction=required|string

    Backend->>CommentService: toggleReaction(comment, reactionType)
    CommentService->>DB: Check existing reaction (same user + same type)
    alt Already exists
        CommentService->>DB: Delete reaction (remove)
    else Doesn't exist
        CommentService->>DB: Create reaction (add)
    end
    Backend->>Browser: JSON {success, data: CommentReactionData[]}
    Note over Browser: CommentReactionData = reaction group counts
```

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| POST | `/comments` | `CommentController@store` |
| PUT | `/comments/{comment}` | `CommentController@update` |
| DELETE | `/comments/{comment}` | `CommentController@destroy` |
| POST | `/comments/{comment}/reaction` | `CommentController@react` |

## Frontend

| File | Purpose |
|------|---------|
| `pages/project/task/partials/TaskComments.vue` | Comment thread: MentionEditor input + CommentItem list |
| `components/ui/comment/CommentItem.vue` | Individual comment: user avatar, body (rendered HTML), timestamp, reply button, edit/delete, reaction icons |
| `components/ui/comment/CommentEditor.vue` | Comment input dengan @mention support |
| `components/Mentioneditor.vue` | Rich text editor wrapper |

**TaskComments.vue - Post comment flow:**
1. Validasi: cek apakah body kosong (parsed HTML textContent) → toast "Empty Comment"
2. `router.post(route('comments.store'), {body, commentable_type:'App\\Models\\Task', commentable_id, parent_id: null})`
3. Success: clear field + `router.reload({only: ['comments']})` + toast
4. Error: toast "Failed to post comment"

**Reaction flow:** Reaction icons on each comment → POST reaction route → response updates reaction group counts in-place.
