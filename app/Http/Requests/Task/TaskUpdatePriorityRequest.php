<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\MsTaskPriority;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdatePriorityRequest extends FormRequest
{
    public ?int $priorityId = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        $decoded = Sqids::decode($value);
                    } catch (\Throwable $th) {
                        return $fail("The $attribute field is invalid.");
                    }

                    if (! $decoded || ! MsTaskPriority::find($decoded)) {
                        return $fail("The selected $attribute is invalid.");
                    }

                    $this->priorityId = (int) $decoded;
                },
            ],
        ];
    }
}
