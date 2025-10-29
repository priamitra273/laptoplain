<?php

namespace App\Http\Requests\AnalyticServer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServerImportRequest extends FormRequest
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
            'servers.*.ip_address' => 'required|ip|unique:analytic_servers,ip_address',
            'servers.*.lisence' => 'required|string|max:255',
            'servers.*.is_alive' => 'required|boolean',
            'servers.*.is_active' => 'required|boolean',
            
            'servers.*.cpu_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.gpu_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.ram_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.ssd_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.mobo_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.nic_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.psu_id' => 'nullable|integer|exists:master_hardware,id',
            'servers.*.lc_id' => 'nullable|integer|exists:master_hardware,id',
        ];
    }
}