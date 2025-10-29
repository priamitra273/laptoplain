<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallationTableRequest extends FormRequest
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
            'rows' => ['nullable', 'numeric'],
            'sortField' => ['nullable', 'string', Rule::in([
                'project_name',
                'regency_name',
                'site_id',
                'site_name',
                'latitude',
                'longitude',
                'status',
                'updated_at'
            ])],
            'sortOrder' => ['nullable', 'numeric', 'min:-1', 'max:1'],
            'filters' => ['nullable', 'array'],
            'q' => ['nullable', 'string']
        ];
    }
}
