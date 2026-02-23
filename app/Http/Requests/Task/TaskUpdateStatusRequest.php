<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateStatusRequest extends FormRequest
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
            'status_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        $id = Sqids::decode($value);
                    } catch (\Throwable $th) {
                        $fail("The $attribute field is invalid.");
                    }

                    if (! MsTaskStatus::where('id', $id)->exists()) {
                        $fail("The $attribute field does not exist.");
                    }
                },
            ],
        ];
    }
}
