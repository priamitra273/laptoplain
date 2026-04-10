<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // validate exists parent id
                        try {
                            $parent_id = Sqids::decode($value);
                            $project_id = Sqids::decode($this->route('encoded'));
                            $parent = Task::where('id', $parent_id)->where('project_id', $project_id)->firstOrFail();
                        } catch (\Throwable $th) {
                            $fail("The $attribute must be a valid task id.");
                        }

                        $task = $this->route('task');

                        // validate parent not be child of task
                        if ($parent_id === $task->id) {
                            $fail("The $attribute cannot be a child of the task.");
                        }

                        // validate descendant task parent
                        $cursor = $parent;
                        while ($cursor) {
                            if ($cursor->id === $task->id) {
                                $fail("The $attribute cannot move task under its own descendant.");
                            }

                            $cursor = $cursor->parent;
                        }
                    }
                },
            ],
        ];
    }
}
