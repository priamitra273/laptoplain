<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
{
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
            'project_id'      => 'required|exists:projects,id',
            'parent_id'       => 'nullable|exists:tasks,id',
            'status_id'       => 'required|exists:ms_task_statuses,id',
            'priority_id'     => 'required|exists:ms_task_priorities,id',
            'type_id'         => 'required|exists:ms_task_types,id',
            'owned_id'        => 'nullable|exists:users,id',
            'emoji'           => 'nullable|string|max:100',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'progress'        => 'nullable|numeric|min:0|max:100',
            'sequence_number' => 'nullable|integer',
            'is_archived'     => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'emoji.max' => 'The emoji may not be greater than 100 characters.',
            'status_id.required' => 'The status field is required.',
            'status_id.exists' => 'The selected status is invalid.',
            'priority_id.required' => 'The priority field is required.',
            'priority_id.exists' => 'The selected priority is invalid.',
            'type_id.required' => 'The type field is required.',
            'type_id.exists' => 'The selected type is invalid.',
            'owned_id.exists' => 'The selected owner is invalid.',
            'progress.numeric' => 'The progress must be a number.',
        ];
    }


    /**
     * Prepare the data for validation.
     *
     * @return void
     */

    protected function prepareForValidation()
    {
        if (!$this->has('owned_id')) {
            $this->merge([
                'owned_id' => Auth::id(),
            ]);
        }

        $statusId = $this->status_id;
        $priorityId = $this->priority_id;
        $typeId = $this->type_id;
        $projectId = $this->project_id;
        $ownedId = $this->owned_id;
        $parentId = $this->parent_id;

        $this->merge([
            'status_id' => is_string($statusId) ? Sqids::decode($statusId) : $statusId,
            'priority_id' => is_string($priorityId) ? Sqids::decode($priorityId) : $priorityId,
            'type_id' => is_string($typeId) ? Sqids::decode($typeId) : $typeId,
            'project_id' => is_string($projectId) ? Sqids::decode($projectId) : $projectId,
            'owned_id' => is_string($ownedId) ? Sqids::decode($ownedId) : $ownedId,
            'parent_id' => is_string($parentId) ? Sqids::decode($parentId) : $parentId,
        ]);
    }

    /**
     * Get the data that should be validated.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }
}
