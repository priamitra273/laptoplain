<?php

namespace App\Http\Requests\Project;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProjectStoreRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|required|string',
            'emoji' => 'nullable|required|string|max:10',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'status_id' => 'required|exists:ms_project_statuses,id',
            'priority_id' => 'required|exists:ms_project_priority,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Project title is required.',
            'start_date.required' => 'Start date is required.',
            'due_date.required' => 'Due date is required.',
            'due_date.after_or_equal' => 'The due date cannot be earlier than the start date.',
            'description.required' => 'Description is required.',
            'emoji.required' => 'Emoji is required.',
            'emoji.max' => 'Emoji may not be greater than 2 characters.',
            'emoji.string' => 'Emoji must be a string.',
            'status_id.required' => 'Status is required.',
            'status_id.exists' => 'The selected status is invalid.',
            'priority_id.required' => 'Priority is required.',
            'priority_id.exists' => 'The selected priority is invalid.',
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

        $this->merge([
            'status_id'   => is_string($statusId) ? Sqids::decode($statusId) : $statusId,
            'priority_id' => is_string($priorityId) ? Sqids::decode($priorityId) : $priorityId,
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
