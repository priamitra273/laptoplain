<?php

namespace App\Http\Requests\MsProjectRole;

use App\Enums\TaskField;
use App\Models\MsTaskStatus;
use App\Rules\SqidExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MsProjectRoleRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'owned_id' => 'nullable|integer|exists:users,id',
            'config' => 'nullable|array',

            'config.task' => 'sometimes|array',
            'config.task.*' => 'sometimes|string|in:create,update,delete',

            'config.project_member' => 'sometimes|array',
            'config.project_member.*' => 'sometimes|string|in:create,update,delete',

            'config.sprint' => 'sometimes|array',
            'config.sprint.*' => 'sometimes|string|in:create,update,delete',

            'config.allow_task_status' => 'sometimes|array',
            'config.allow_task_status.*' => ['sometimes', 'string', new SqidExists(MsTaskStatus::class)],

            'config.allow_update_task_fields' => 'sometimes|array',
            'config.allow_update_task_fields.*' => ['sometimes', 'string', Rule::in(TaskField::cases())],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (! $this->has('owned_id')) {
            $this->merge([
                'owned_id' => Auth::id(),
            ]);
        }
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
