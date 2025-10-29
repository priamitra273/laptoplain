<?php

namespace App\Imports;

use App\Models\Hardware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class ServerStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $this->getValidatedData($array);
    }

    public function rules(): array
    {
        return [
            'ip_address' => 'required|ip|unique:analytic_servers,ip_address',
            'lisence' => 'required|string|max:255',
            'is_alive' => 'required|boolean',
            'is_active' => 'required|boolean',
            
            'cpu_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'gpu_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'ram_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'ssd_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'mobo_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'nic_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'psu_sn' => 'nullable|string|exists:master_hardware,serial_number',
            'lc_sn' => 'nullable|string|exists:master_hardware,serial_number',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        $hardwareFields = [
            'cpu_sn' => 'cpu_id',
            'gpu_sn' => 'gpu_id',
            'ram_sn' => 'ram_id',
            'ssd_sn' => 'ssd_id',
            'mobo_sn' => 'mobo_id',
            'nic_sn' => 'nic_id',
            'psu_sn' => 'psu_id',
            'lc_sn' => 'lc_id',
        ];

        foreach ($hardwareFields as $nameField => $idField) {
            if (isset($data[$nameField]) && !empty($data[$nameField])) {
                $hardware = Hardware::where('serial_number', $data[$nameField])->first();
                $data[$idField] = $hardware ? $hardware->id : null;
            }
        }

        if (isset($data['is_alive'])) {
            $data['is_alive'] = filter_var($data['is_alive'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    public function getValidatedData(array $array)
    {
        $safe_data = [];

        foreach ($array as $row) {
            $preparedRow = $this->prepareForValidation($row, 0);
            $validator = Validator::make($preparedRow, $this->rules());

            $data = Arr::only($validator->getData(), $this->getHeader());
            
            $hardwareIds = ['cpu_id', 'gpu_id', 'ram_id', 'ssd_id', 'mobo_id', 'nic_id', 'psu_id', 'lc_id'];
            foreach ($hardwareIds as $idField) {
                if (isset($preparedRow[$idField])) {
                    $data[$idField] = $preparedRow[$idField];
                }
            }
            
            $data['errors'] = $validator->errors();

            $safe_data[] = $data;
        }

        return $safe_data;
    }

    public function getHeader(): array
    {
        return array_keys($this->rules());
    }
}