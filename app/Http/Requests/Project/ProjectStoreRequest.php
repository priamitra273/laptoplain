<?php

namespace App\Http\Requests\Project;

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
            'description' => 'nullable|string',
            'emoji' => 'nullable|string|max:10',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'status_id' => 'required|exists:ms_project_statuses,id',
            'priority_id' => 'required|exists:ms_project_priority,id',
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
                'owned_id' => Auth::id()
            ]);
        }
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
