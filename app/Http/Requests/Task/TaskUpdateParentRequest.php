<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|exists:tasks,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $parentId = $this->input('parent_id');

        if (is_string($parentId) && $parentId !== '') {
            $this->merge([
                'parent_id' => Sqids::decode($parentId),
            ]);
        }
    }
}
