<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use App\Rules\SqidExists;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                new SqidExists(MsTaskStatus::class),
            ],

            'due_date' => [
                'sometimes',
                'nullable',
                'date',
                Rule::requiredIf(function () {
                    try {
                        $decoded_status_id = Sqids::decode($this->status_id);
                        $status = MsTaskStatus::findOrFail($decoded_status_id);
                    } catch (\Throwable $th) {
                        return false;
                    }

                    $task = $this->route('task');
                    $exclude_status = ['To Do', 'Blocked'];

                    return ! in_array($status->name, $exclude_status) && ! $task->due_date;
                }),
                function ($attribute, $value, $fail) {
                    if ($value && $due_date = Carbon::parse($value)) {
                        $task = $this->route('task');
                        $start_date = Carbon::parse($task->start_date);

                        if ($start_date && $due_date->lt($start_date)) {
                            $fail('Due date cannot be earlier than the start date.');
                        }
                    }
                },
            ],
        ];
    }
}
