<?php

namespace App\Http\Requests\Hardware;

use Illuminate\Foundation\Http\FormRequest;

class HardwareStoreRequest extends FormRequest
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
            'po_number' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:master_hardware,serial_number'],
            'category' => ['required', 'uuid', 'exists:App\Models\HardwareComponent,uuid'],
            'brand' => ['required', 'array', 'required_array_keys:uuid,name'],
            'brand.uuid' => ['nullable', 'uuid', 'exists:App\Models\Hardware\Brand,uuid'],
            'brand.name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string'],
            'remarks' => 'required|string'
        ];
    }
}
