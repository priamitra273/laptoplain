<?php

namespace App\Http\Requests\Sprint;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;

class SprintCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $existingSprint = $this->input('move_incomplete_to.existing_sprint');

        if ($existingSprint && is_string($existingSprint)) {
            $decoded = Sqids::decode($existingSprint);

            $this->merge([
                'move_incomplete_to' => array_merge(
                    $this->input('move_incomplete_to', []),
                    ['existing_sprint' => $decoded ?: null]
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'retrospective' => 'nullable|string',
            'move_incomplete_to.existing_sprint' => 'nullable|integer|exists:project_sprints,id',
            'move_incomplete_to.other' => 'nullable|string|in:backlog,new_sprint',
        ];
    }
}
