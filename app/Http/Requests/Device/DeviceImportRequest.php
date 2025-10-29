<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class DeviceImportRequest extends FormRequest
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
            'devices.*.mac_address' => 'required|mac_address|unique:App\Models\Device,mac_address',
            'devices.*.ip_dhcp' => 'required|ip',
            'devices.*.ip_static' => 'nullable|ip',
        ];
    }
}
