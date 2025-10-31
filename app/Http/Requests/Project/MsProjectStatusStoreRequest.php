<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class MsProjectStatusStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // izinkan semua user login
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama status wajib diisi.',
            'severity.required' => 'Severity wajib diisi.',
        ];
    }
}
