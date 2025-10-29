<?php

namespace App\Http\Requests\AnalyticServer;

use App\Models\Hardware;
use App\Models\Hardware\HardwareStatus;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;

class MaintenanceServerRequest extends FormRequest
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
            'replacements' => [
                'required',
                'array',
                'required_array_keys:cpu,gpu,ram,ssd,mobo,nic,psu,lc'
            ],
            'replacements.*.current_uuid' => 'nullable|uuid|exists:App\Models\Hardware,uuid',
            'replacements.*.status_uuid' => [
                'required_with:replacements.*.current_uuid',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value && !Uuid::isValid($value)) {
                        $fail("The selected status is invalid.");
                    }

                    if ($value && !HardwareStatus::findByUuid($value)) {
                        $fail("The selected status is invalid.");
                    }
                }
            ],
            'replacements.*.replacement_uuid' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    $formData = collect($this->validationData())->dot();

                    $current_uuid = $formData->get(str_replace('replacement_uuid', 'current_uuid', $attribute));
                    $status_uuid = $formData->get(str_replace('replacement_uuid', 'status_uuid', $attribute));

                    if ($current_uuid || $status_uuid) {
                        $fail("The {$attribute} is required.");
                    }

                    if ($value) {
                        if (!Uuid::isValid($value)) {
                            $fail("The selected replacement item is invalid.");
                        }

                        $hardware = Hardware::whereUuid($value)->unused()->first();

                        if (!$hardware) {
                            $fail("The {$attribute} has taken by another server.");
                        }
                    }
                }
            ]
        ];
    }

    public function attributes(): array
    {
        return [
            'replacements.*.current_uuid' => 'hardware',
            'replacements.*.status_uuid' => 'status',
            'replacements.*.replacement_uuid' => 'replacement item'
        ];
    }
}
