<?php

namespace App\Http\Requests\Project;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'emoji' => 'sometimes|nullable|string|max:10',
            'start_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status_id' => 'sometimes|required|exists:ms_project_statuses,id',
            'priority_id' => 'sometimes|required|exists:ms_project_priority,id',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('owned_id')) {
            $this->merge([
                'owned_id' => Auth::id(),
            ]);
        }

        $statusId = $this->status_id;
        $priorityId = $this->priority_id;

        $this->merge([
            'status_id'   => is_string($statusId) ? Sqids::decode($statusId) : $statusId,
            'priority_id' => is_string($priorityId) ? Sqids::decode($priorityId) : $priorityId,
        ]);
    }
}
