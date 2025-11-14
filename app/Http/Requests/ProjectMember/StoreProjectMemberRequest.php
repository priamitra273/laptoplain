<?php

namespace App\Http\Requests\ProjectMember;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProjectMemberRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'project_role_id' => 'required|exists:ms_project_roles,id',
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
                'owned_id' => Auth::id()
            ]);
        }
        $this->merge([
            'user_id' => $this->filled('user_id')
                ? Sqids::decode($this->user_id)
                : null,
            'project_role_id' => $this->filled('project_role_id')
                ? Sqids::decode($this->project_role_id)
                : null,
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
