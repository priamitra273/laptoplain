<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
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
            'label' => 'required|string|max:255',
            'team_uuid' => [
                'required',
                'uuid',
                Rule::exists('teams', 'uuid')
                    ->whereNull('deleted_at')
                    ->when(! Auth::user()->is_super_admin, fn ($q) => $q->where('name', '!=', 'Admin')),
            ],
            'is_active' => 'required|boolean',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name',
        ];
    }

    public function attributes(): array
    {
        return [
            'team_uuid' => 'team',
            'label' => 'role name',
        ];
    }
}
