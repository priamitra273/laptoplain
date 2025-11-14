<?php

namespace App\Http\Requests\Project;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProjectStoreRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|required|string',
            'emoji' => 'nullable|required|string|max:10',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'status_id' => 'required|exists:ms_project_statuses,id',
            'priority_id' => 'required|exists:ms_project_priority,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul proyek wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'due_date.required' => 'Tanggal selesai wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'description.required' => 'Deskripsi wajib diisi.',
            'emoji.required' => 'Emoji wajib diisi.',
            'emoji.max' => 'Emoji maksimal 2 karakter.',
            'status_id.required' => 'Status wajib dipilih.',
            'status_id.exists' => 'Status yang dipilih tidak valid.',
            'priority_id.required' => 'Prioritas wajib dipilih.',
            'priority_id.exists' => 'Prioritas yang dipilih tidak valid.',
            'emoji.string' => 'Emoji harus berupa teks.',
            'emoji.required' => 'Emoji wajib diisi.',
            'description.required' => 'Deskripsi wajib diisi.',
        ];
    }
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (!$this->has('owned_id')) {
            $this->merge([
                'owned_id' => Auth::id(),
            ]);
        }

        $statusId = $this->status_id;
        $priorityId = $this->priority_id;

        $this->merge([
            'status_id'   => is_string($statusId) ? Sqids::decode($statusId) : $statusId,
            'priority_id' => is_string($priorityId) ? Sqids::decode($priorityId) : $priorityId,
        ]);
    }


    /**
     * Get the data that should be validated.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }
}
