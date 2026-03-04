<?php

namespace App\Http\Requests\Comment;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCommentRequest extends FormRequest
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
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|string',
            'parent_id' => 'nullable|string',
        ];
    }

    /**
     * Get the IDs of users mentioned in the comment.
     *
     * Extracts and returns an array of user IDs that are mentioned or tagged
     * within the comment content.
     *
     * @return array An array of user IDs that are mentioned in the comment.
     */
    public function mentionedUserIds(): array
    {
        preg_match_all('/data-id="([^"]+)"/', $this->body, $matches);

        if (empty($matches[1])) {
            return [];
        }

        return collect($matches[1])
            ->map(fn ($encodedId) => Sqids::decode($encodedId))
            ->flatten()
            ->unique()
            ->filter(fn ($id) => $id !== Auth::id())
            ->values()
            ->toArray();
    }
}
