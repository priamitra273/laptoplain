<?php

namespace App\Http\Requests\AnalyticServer;

use App\Models\AnalyticServer;
use App\Models\Hardware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $analyticServer = $this->route('analytic_server');
        
        return [
            'ip_address' => [
                'required', 
                'ip', 
                Rule::unique('analytic_servers')->ignore($analyticServer->id)->withoutTrashed()
            ],
            'lisence' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'is_alive' => 'required|boolean',
            'cpu_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'gpu_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'ram_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'ssd_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'mobo_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'nic_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'psu_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
            'lc_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $this->validateHardwareExists($attribute, $value, $fail);
                    }
                },
            ],
        ];
    }

    /**
     * Validate that hardware exists by checking ID or UUID separately
     */
    private function validateHardwareExists($attribute, $value, $fail)
    {
        if (is_numeric($value)) {
            $exists = Hardware::where('id', $value)->exists();
        } else {
            if (!$this->isValidUuid($value)) {
                $fail("The selected {$attribute} must be a valid ID or UUID.");
                return;
            }
            $exists = Hardware::where('uuid', $value)->exists();
        }

        if (!$exists) {
            $fail("The selected {$attribute} is invalid.");
        }
    }

    /**
     * Check if a string is a valid UUID format
     */
    private function isValidUuid($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get the safe data with hardware UUIDs converted to IDs.
     */
    public function getSafeDataWithConvertedIds(): array
    {
        $data = $this->safe()->toArray();
        
        $hardwareFields = ['cpu_id', 'gpu_id', 'ram_id', 'ssd_id', 'mobo_id', 'nic_id', 'psu_id', 'lc_id'];
        
        foreach ($hardwareFields as $field) {
            if (!empty($data[$field])) {
                if (is_numeric($data[$field])) {
                    $data[$field] = (int) $data[$field];
                } else {
                    $hardware = Hardware::where('uuid', $data[$field])->first();
                    $data[$field] = $hardware ? $hardware->id : null;
                }
            }
        }
        
        return $data;
    }
}