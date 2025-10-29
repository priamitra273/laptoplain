<?php

namespace App\Http\Requests\Analytic;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticUpdateRequest extends FormRequest
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
            'category_uuid' => 'nullable|uuid|exists:App\Models\AnalyticCategory,uuid',
            'department_uuid' => 'nullable|uuid|exists:App\Models\Department,uuid',
            'link_embed_bb' => 'nullable|url',
            'server_uuid' => 'nullable|uuid|exists:App\Models\AnalyticServer,uuid',
            'polygon' => 'nullable|array',
            'polygon.*' => 'nullable|array',
            'polygon.*.*' => 'nullable|numeric',
            'threshold' => 'nullable|array',
            'threshold.*' => 'nullable|numeric'
        ];
    }
}
