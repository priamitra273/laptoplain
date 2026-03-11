<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateStatusRequest extends FormRequest
{
    public ?MsTaskStatus $status = null;

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
                        return $fail("The $attribute field is invalid.");
                    }

                    $status = MsTaskStatus::find($id);

                    if (! $status) {
                        return $fail("The $attribute field does not exist.");
                    }

                    $this->status = $status;
                },
            ],

            'due_date' => 'sometimes|nullable|date|after_or_equal:start_date',
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {

        if (! $this->status) {
            return;
        }

        // ambil encoded task dari route
        $encoded = $this->route('encoded');

        // decode id task
        $taskId = Sqids::decode($encoded);

        // ambil task
        $task = Task::find($taskId);

        if (! $task) {
            return;
        }

        $statusIsInProgress = $this->status->name === 'In Progress';
        $taskHasDueDate = ! is_null($task->due_date);
        $requestHasDueDate = ! is_null($this->due_date);

        if ($statusIsInProgress && ! $taskHasDueDate && ! $requestHasDueDate) {
            $validator->errors()->add(
                'due_date',
                'Due date is required when status is In Progress and the task does not already have one.'
            );
        }
    });
}
}
