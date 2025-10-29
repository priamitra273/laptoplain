<?php

namespace App\Imports;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class HardwareStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $this->getValidatedData($array);
    }

    public function rules(): array
    {
        return [
            'po_number' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:master_hardware,serial_number'],
            'category' => ['required', 'string', 'max:255', 'exists:App\Models\HardwareComponent,code'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'remarks' => 'nullable|string'
        ];
    }

    public function prepareForValidation($data, $index)
    {
        $data['category'] = strtoupper($data['category']);
        $data['brand'] = ucwords($data['brand']);

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
