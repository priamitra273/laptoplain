<?php

namespace App\Imports;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProjectStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $array;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'finish_date' => 'required|date|after_or_equal:start_date',
            'plan_site' => 'required|numeric|min:1',
            'plan_cctv' => 'required|numeric|min:1'
        ];
    }

    /**
     * Transform a date value into a Carbon object.
     *
     * @return \Carbon\Carbon|null
     */
    public function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }

    public function prepareForValidation($data, $index)
    {
        $data['start_date'] = $data['start_date'] ? $this->transformDate($data['start_date'])->toDateString() : null;
        $data['finish_date'] = $data['finish_date'] ? $this->transformDate($data['finish_date'])->toDateString() : null;

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
