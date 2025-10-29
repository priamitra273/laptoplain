<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class PreconfigUpdateRequest extends FormRequest
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
            'cctv' => 'required|array|min:1',
            'cctv.*' => 'required|array',
            'cctv.*.uuid' => 'nullable|uuid|exists:App\Models\Cctv',
            'cctv.*.cctv_name' => 'required|string|max:255',
            'cctv.*.device_id' => 'required|uuid|distinct|exists:App\Models\Device,uuid'
        ];
    }

    public function attributes(): array
    {
        return [
            'cctv.*.cctv_name' => 'CCTV Name',
            'cctv.*.device_id' => 'Mac Address'
        ];
    }
}
