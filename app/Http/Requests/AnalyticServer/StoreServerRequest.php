<?php

namespace App\Http\Requests\AnalyticServer;

use App\Models\AnalyticServer;
use App\Models\Hardware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServerRequest extends FormRequest
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
        return [
            'ip_address' => ['required', 'ip', 'unique:analytic_servers'],
            'lisence' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'is_alive' => 'required|boolean',
            'cpu_id' => 'nullable|exists:master_hardware,uuid',
            'gpu_id' => 'nullable|exists:master_hardware,uuid',
            'ram_id' => 'nullable|exists:master_hardware,uuid',
            'ssd_id' => 'nullable|exists:master_hardware,uuid',
            'mobo_id' => 'nullable|exists:master_hardware,uuid',
            'nic_id' => 'nullable|exists:master_hardware,uuid',
            'psu_id' => 'nullable|exists:master_hardware,uuid',
            'lc_id' => 'nullable|exists:master_hardware,uuid',
        ];
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
                $hardware = Hardware::where('uuid', $data[$field])->first();
                $data[$field] = $hardware ? $hardware->id : null;
            }
        }
        
        return $data;
    }
}