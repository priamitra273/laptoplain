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
        return false;
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

    public function newMentionedUserIds(Comment $comment): array
    {
        $oldMentions = $this->extractMentionIds($comment->body);
        $newMentions = $this->extractMentionIds($this->body);

        return collect(array_diff($newMentions, $oldMentions))
            ->filter(fn ($id) => $id !== Auth::id())
            ->values()
            ->toArray();
    }

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
