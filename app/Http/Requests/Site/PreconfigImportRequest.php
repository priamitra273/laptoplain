<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreconfigImportRequest extends FormRequest
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
            'preconfigs.*.project_id' => 'required|integer|exists:projects,id',
            'preconfigs.*.regency_id' => 'required|integer|exists:regencies,id',
            'preconfigs.*.site_id' => 'required|integer|min_digits:6|exists:sites,site_id',
            'preconfigs.*.site_name' => 'required|string|max:255',
            'preconfigs.*.latitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'preconfigs.*.longitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'preconfigs.*.cctv_name' => 'required|string|max:255',
            'preconfigs.*.cctv_id' => 'nullable|integer|exists:cctvs,id',
            'preconfigs.*.device_id' => 'required|integer|exists:devices,id',
            'preconfigs.*.site_uuid' => 'required|string|exists:sites,uuid',
            'preconfigs.*.device_uuid' => 'required|string|exists:devices,uuid',
        ];
    }
}