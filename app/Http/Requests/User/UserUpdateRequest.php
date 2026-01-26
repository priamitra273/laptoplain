<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user, 'uuid')->withoutTrashed()],
            'role_id' => 'required|numeric|exists:roles,id',
            'is_active' => 'required|boolean',
            'password' => 'nullable|string|min:8',
            'password_confirmation' => 'confirmed:password',
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'The avatar must be a file of type: jpeg, jpg, png, gif.',
            'avatar.max' => 'The avatar must not be greater than 2MB.',
        ];
    }
}
