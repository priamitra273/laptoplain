<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteImportRequest extends FormRequest
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
            'sites.*.site_category' => 'required|string|in:New,Replacement',

            'sites.*.replacement_to' => [
                'nullable',
                'required_if:sites.*.site_category,Replacement',
                'integer',
                Rule::exists('sites', 'id')->where('site_status_id', 4)->withoutTrashed(),
            ],

            // Still validate the IDs since that's what gets stored in database
            'sites.*.regency_id' => 'required|integer|exists:regencies,id',
            'sites.*.project_id' => 'required|integer|exists:projects,id',
            'sites.*.site_id' => 'required|integer|min_digits:6|unique:sites,site_id',
            'sites.*.site_name' => 'required|string|max:255',
            'sites.*.latitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'sites.*.longitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'sites.*.site_status_id' => 'required|integer|exists:site_statuses,id',
        ];
    }
}