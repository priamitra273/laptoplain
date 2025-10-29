<?php

namespace App\Http\Requests\Site;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteStoreRequest extends FormRequest
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
                Rule::exists('sites', 'uuid')->where('site_status_id', 4)->withoutTrashed()
            ],
            'regency_id' => 'required|uuid|exists:App\Models\Regency,uuid',
            'project_id' => 'required|uuid:4|exists:App\Models\Project,uuid',
            'site_id' => 'required|integer||min_digits:6|unique:App\Models\Site,site_id',
            'site_name' => 'required|string|max:255',
            'latitude' => ['required', Rule::numeric()->decimal(6,18)],
            'longitude' => ['required', Rule::numeric()->decimal(6,18)],
            // 'site_status_id' => 'required|uuid|exists:App\Models\SiteStatus,uuid',
            // 'remark' => 'required|string',
            // 'attachments' => 'nullable|array',
            // 'attachments.*' => 'nullable|file',
            // 'cctv' => 'required|array|min:1',
            // 'cctv.*' => 'required|array',
            // 'cctv.*.cctv_name' => 'required|string|max:255',
            // 'cctv.*.device_id' => 'required|uuid|distinct|exists:App\Models\Device,uuid'
        ];
    }

    public function attributes(): array
    {
        return [
            'cctv.*.cctv_name' => 'CCTV Name',
            'cctv.*.device_id' => 'Mac Address'
        ];
    }

    public function passedValidation(): void
    {
        $this->merge([
            'project_id' => Project::findByUuid($this->project_id)->id,
            'replacement_to' => $this->replacement_to ? Project::findByUuid($this->replacement_to)->id : null,
        ]);

        $this->request->remove('site_category');
    }
}
