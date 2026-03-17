<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use Illuminate\Foundation\Http\FormRequest;

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
                function ($attribute, $value, $fail) {
                    try {
                        $id = Sqids::decode($value);
                    } catch (\Throwable $th) {
                        $fail("The $attribute field is invalid.");
                    }

                    if (! MsTaskStatus::where('id', $id)->exists()) {
                        $fail("The $attribute field does not exist.");
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $isProductOwner = $user->getRoleNames()->contains(fn ($role) => str_starts_with($role, 'product-owner-'));
            if (! $isProductOwner) {
                return;
            }

            try {
                $statusId = Sqids::decode((string) $this->input('status_id'));
            } catch (\Throwable $th) {
                return;
            }

            $statusName = (string) optional(MsTaskStatus::find($statusId))->name;
            $normalized = mb_strtolower(trim($statusName));
            $allowed = ['to do', 'complete', 'completed', 'block', 'blocked'];

            if (! in_array($normalized, $allowed, true)) {
                $validator->errors()->add(
                    'status_id',
                    'Product Owner can only set status to To Do, Complete(d), or Block.'
                );
            }
        });
    }
}
