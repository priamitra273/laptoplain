<?php

namespace App\Http\Requests\Stream;

use Illuminate\Foundation\Http\FormRequest;

class StreamTableRequest extends FormRequest
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
            'sortField' => ['nullable', 'string', 'in:project_name,site_id,site_name,latitude,longitude,status,created_at'],
            'sortOrder' => ['nullable', 'numeric', 'min:-1', 'max:1'],
            'q' => ['nullable', 'string']
        ];
    }
}
