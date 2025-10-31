<?php

namespace App\Http\Requests\MsProjectStatus;

use Illuminate\Foundation\Http\FormRequest;

class MsProjectStatusImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Bisa disesuaikan jika perlu pembatasan akses
    }

    public function rules(): array
    {
        return [
            'file' => ['nullable'], // Tidak wajib karena belum ada fitur import
        ];
    }

    public function messages(): array
    {
        return [
            'file.nullable' => 'Kolom file bersifat opsional.',
        ];
    }
}
