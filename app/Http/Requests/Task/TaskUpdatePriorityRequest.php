<?php

namespace App\Http\Requests\Task;

use App\Models\MsTaskPriority;
use App\Rules\SqidExists;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdatePriorityRequest extends FormRequest
{
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
                new SqidExists(MsTaskPriority::class),
            ],
        ];
    }
}
