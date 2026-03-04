<?php

namespace App\Http\Requests\TaskReport;

use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\User;
use App\Rules\SqidExists;
use Illuminate\Foundation\Http\FormRequest;

class TaskReportIndexRequest extends FormRequest
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
            'names' => 'sometimes|array',
            'names.*' => ['sometimes', 'string', new SqidExists(User::class)],
            'statuses' => 'sometimes|array',
            'statuses.*' => ['sometimes', 'string', new SqidExists(MsTaskStatus::class)],
            'priorities' => 'sometimes|array',
            'priorities.*' => ['sometimes', 'string', new SqidExists(MsTaskPriority::class)],
            'types' => 'sometimes|array',
            'types.*' => ['sometimes', 'string', new SqidExists(MsTaskType::class)],
            'start_date_from' => 'sometimes|date',
            'start_date_to' => 'sometimes|date',
            'due_date_from' => 'sometimes|date',
            'due_date_to' => 'sometimes|date',
            'search' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
