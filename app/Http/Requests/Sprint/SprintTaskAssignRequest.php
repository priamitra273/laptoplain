<?php

namespace App\Http\Requests\Sprint;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;

class SprintTaskAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('task_ids')) {
            $this->merge([
                'task_ids' => collect($this->task_ids)
                    ->map(fn ($id) => Sqids::decode($id))
                    ->filter()
                    ->values()
                    ->toArray(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'task_ids'   => 'required|array',
            'task_ids.*' => 'integer|exists:tasks,id',
        ];
    }
}
