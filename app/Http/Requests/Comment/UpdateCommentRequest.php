<?php

namespace App\Http\Requests\Comment;

use App\Facades\Sqids;
use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'required|string',
        ];
    }

    /**
     * Get the IDs of newly mentioned users in the updated comment.
     *
     * Compares the mention IDs from the old comment body with the new comment body
     * and returns only the newly added mentions, excluding the current authenticated user.
     *
     * @param Comment $comment The original comment to compare against
     * @return array An array of newly mentioned user IDs
     */
    public function newMentionedUserIds(Comment $comment): array
    {
        $oldMentions = $this->extractMentionIds($comment->body);
        $newMentions = $this->extractMentionIds($this->body);

        return collect(array_diff($newMentions, $oldMentions))
            ->filter(fn ($id) => $id !== Auth::id())
            ->values()
            ->toArray();
    }

    /**
     * Extract user mention IDs from a comment body.
     *
     * Parses the given body string to find all data-id attributes matching the mention
     * pattern and decodes them using Sqids. Returns unique, non-empty IDs.
     *
     * @param string $body The comment body text containing encoded mention IDs
     * @return array An array of decoded unique mention user IDs
     */
    private function extractMentionIds(string $body): array
    {
        preg_match_all('/data-id="([^"]+)"/', $body, $matches);

        if (empty($matches[1])) {
            return [];
        }

        return collect($matches[1])
            ->map(fn ($encodedId) => Sqids::decode($encodedId))
            ->flatten()
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }
}
