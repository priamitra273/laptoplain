<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
            'email' => 'required|email|unique:App\Models\User,email',
            'role_id' => 'required|numeric|exists:roles,id',
            'is_active' => 'required|boolean',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'confirmed:password'
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => 'role'
        ];
    }
}
