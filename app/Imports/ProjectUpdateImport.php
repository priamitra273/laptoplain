<?php

namespace App\Imports;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProjectUpdateImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $array;
    }

    /**
     * Transform a date value into a Carbon object.
     *
     * @return \Carbon\Carbon|null
     */
    public function transformDate($value, $format = 'Y-m-d')
    {
        if (is_string($value)) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }

        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|uuid|exists:App\Models\Project',
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'finish_date' => 'sometimes|date|after_or_equal:start_date',
            'plan_site' => 'sometimes|numeric|min:1',
            'plan_cctv' => 'sometimes|numeric|min:1'
        ];
    }

    public function prepareForValidation($data, $index)
    {
        if ($data['start_date']) {
            $data['start_date'] = $this->transformDate($data['start_date'])->toDateString();
        }

        if ($data['finish_date']) {
            $data['finish_date'] = $this->transformDate($data['finish_date'])->toDateString();
        }

        return $data;
    }

    /**
     * @param array $array
     */
    public function getValidatedData(array $array)
    {
        $safe_data = [];

        foreach ($array as $row) {
            $validator = Validator::make($row, $this->rules());

            $data = Arr::only($validator->getData(), $this->getHeader());

            $data['uuid'] = $data['uuid'] ?? null;

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
