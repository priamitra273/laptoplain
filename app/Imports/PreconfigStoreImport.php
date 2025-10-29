<?php

namespace App\Imports;

use App\Models\Cctv;
use App\Models\Regency;
use App\Models\Project;
use App\Models\Site;
use App\Models\Device;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class PreconfigStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $this->getValidatedData($array);
    }

    public function rules(): array
    {
        return [
            'project_name' => 'required|string|exists:projects,name',
            'regency_name' => 'required|string|exists:regencies,name',
            'site_id' => 'required|integer|min_digits:6|exists:sites,site_id',
            'site_name' => 'required|string|max:255',
            'latitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'longitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'cctv_name' => 'required|string|max:255',
            'mac_address' => 'required|mac_address|exists:devices,mac_address',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        if (isset($data['regency_name'])) {
            $regency = Regency::where('name', $data['regency_name'])->first();
            $data['regency_id'] = $regency ? $regency->id : null;
        }

        if (isset($data['project_name'])) {
            $project = Project::where('name', $data['project_name'])->first();
            $data['project_id'] = $project ? $project->id : null;
        }

        if (isset($data['cctv_name'])) {
            $cctv = Cctv::where('name', $data['cctv_name'])->first();
            $data['cctv_id'] = $cctv ? $cctv->id : null;
        }

        if (isset($data['site_id'])) {
            $site = Site::where('site_id', $data['site_id'])->first();
            $data['site_uuid'] = $site ? $site->uuid : null;
        }

        if (isset($data['mac_address'])) {
            $device = Device::where('mac_address', $data['mac_address'])->first();
            $data['device_id'] = $device ? $device->id : null;
            $data['device_uuid'] = $device ? $device->uuid : null;
        }

        return $data;
    }

    public function getValidatedData(array $array)
    {
        $safe_data = [];

        foreach ($array as $index => $row) {
            $preparedRow = $this->prepareForValidation($row, $index);
            $validator = Validator::make($preparedRow, $this->rules());
            
            $validator->after(function ($validator) use ($preparedRow) {
                if (!isset($preparedRow['project_id']) || !$preparedRow['project_id']) {
                    $validator->errors()->add('project_name', 'Project not found');
                }
                
                if (!isset($preparedRow['regency_id']) || !$preparedRow['regency_id']) {
                    $validator->errors()->add('regency_name', 'Regency not found');
                }
                
                if (!isset($preparedRow['site_uuid']) || !$preparedRow['site_uuid']) {
                    $validator->errors()->add('site_id', 'Site not found');
                }
                
                if (!isset($preparedRow['device_id']) || !$preparedRow['device_id']) {
                    $validator->errors()->add('mac_address', 'Device not found');
                }
            });
            $data = Arr::only($preparedRow, $this->getHeader());
            
            $data['regency_id'] = $preparedRow['regency_id'] ?? null;
            $data['project_id'] = $preparedRow['project_id'] ?? null;
            $data['cctv_id'] = $preparedRow['cctv_id'] ?? null;
            $data['site_uuid'] = $preparedRow['site_uuid'] ?? null;
            $data['device_id'] = $preparedRow['device_id'] ?? null;
            $data['device_uuid'] = $preparedRow['device_uuid'] ?? null;
            
            $errors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[$field] = $messages;
            }
            $data['errors'] = $errors;

            $safe_data[] = $data;
        }

        return $safe_data;
    }

    public function getHeader(): array
    {
        return array_keys($this->rules());
    }
}