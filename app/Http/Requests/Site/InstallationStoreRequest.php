<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class InstallationStoreRequest extends FormRequest
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
            'site_status_uuid' => 'required|uuid|exists:App\Models\SiteStatus,uuid',
            'remark' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file',
        ];
    }

    public function attributes()
    {
        return [
            'site_status_uuid' => 'status',
            'attachments.*' => 'attachments'
        ];
    }
}
