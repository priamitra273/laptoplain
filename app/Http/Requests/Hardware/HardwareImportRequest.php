<?php

namespace App\Http\Requests\Hardware;

use Illuminate\Foundation\Http\FormRequest;

class HardwareImportRequest extends FormRequest
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
            'hardwares.*.po_number' => ['required', 'string', 'max:255'],
            'hardwares.*.serial_number' => ['required', 'string', 'max:255', 'unique:master_hardware,serial_number'],
            'hardwares.*.category' => ['required', 'exists:App\Models\HardwareComponent,code'],
            'hardwares.*.brand' => ['required', 'string', 'max:255'],
            'hardwares.*.model' => ['nullable', 'string', 'max:255'],
            'hardwares.*.remarks' => 'nullable|string'
        ];
    }
}
