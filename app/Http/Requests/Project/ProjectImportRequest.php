<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ProjectImportRequest extends FormRequest
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
            'projects.*.name' => 'required|string|max:255',
            'projects.*.start_date' => 'required|date',
            'projects.*.finish_date' => 'required|date|after_or_equal:start_date',
            'projects.*.plan_site' => 'required|numeric|min:1',
            'projects.*.plan_cctv' => 'required|numeric|min:1'
        ];
    }
}
