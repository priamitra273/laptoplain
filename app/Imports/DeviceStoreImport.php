<?php

namespace App\Imports;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DeviceStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $this->getValidatedData($array);
    }

    public function rules(): array
    {
        return [
            'mac_address' => 'required|mac_address|unique:App\Models\Device,mac_address',
            'ip_dhcp' => 'required|ip',
            'ip_static' => 'nullable|ip',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        return $data;
    }

    public function getValidatedData(array $array)
    {
        $validated = [];

        foreach ($array as $row) {
            $validator = Validator::make($row, $this->rules());

            $data = Arr::only($validator->getData(), $this->getHeader());
            $data['errors'] = $validator->errors();

            $validated[] = $data;
        }

        return $validated;
    }

    public function getHeader(): array
    {
        return array_keys($this->rules());
    }
}
