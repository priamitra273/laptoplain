<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteUpdateRequest extends FormRequest
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
            'site_category' => 'required|string|in:New,Replacement',
            'replacement_to' => [
                'nullable',
                'required_if:site_category,Replacement',
                'uuid:4',
                Rule::exists('sites', 'uuid')
                    ->where('site_status_id', 4)
                    ->where(fn ($q) => $q->whereDoesntHave('replaced_by'))
                    ->withoutTrashed()
            ],
            'site_id' => [
                'required', 
                'integer', 
                Rule::unique('sites')->ignore($this->site->id)->withoutTrashed()
            ],
            'regency_id' => 'required|uuid|exists:App\Models\Regency,uuid',
            'site_name' => 'required|string|max:255',
            'latitude' => ['required', Rule::numeric()->decimal(6,18)],
            'longitude' => ['required', Rule::numeric()->decimal(6,18)],
            'project_id' => 'required|uuid:4|exists:App\Models\Project,uuid',
            // 'site_status_id' => 'required|uuid|exists:App\Models\SiteStatus,uuid',
            // 'remark' => 'required|string',
            // 'attachments' => 'nullable|array',
            // 'attachments.*' => 'nullable|file',
            // 'cctv' => 'required|array|min:1',
            // 'cctv.*' => 'required|array',
            // 'cctv.*.uuid' => 'nullable|uuid|exists:App\Models\Cctv',
            // 'cctv.*.cctv_name' => 'required|string|max:255',
            // 'cctv.*.device_id' => 'required|uuid|distinct|exists:App\Models\Device,uuid'
        ];
    }
}
