<?php

namespace App\Imports;

use App\Models\Cctv;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StreamStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $this->getValidatedData($array);
    }

    public function rules(): array
    {
        return [
            'cctv_name' => 'required|string|exists:cctvs,name',
            'ip_flussonic' => 'nullable|ip',
            'ip_static' => 'nullable|ip',
            'link_embed' => 'nullable|url',
            'link_embed_nonrelay' => 'nullable|url',
            'link_rtsp' => 'required|string|max:255|regex:/^rtsp:\/\/([a-zA-Z0-9\-_]+(:[a-zA-Z0-9\-_]+)?@)?[a-zA-Z0-9\-_]+(\.[a-zA-Z0-9\-_]+)*(:[0-9]+)?(\/[a-zA-Z0-9\-_]+)*$/',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        if (isset($data['cctv_name'])) {
            $cctv = Cctv::where('name', $data['cctv_name'])->first();
            $data['cctv_id'] = $cctv ? $cctv->id : null;
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
                if (!isset($preparedRow['cctv_id']) || !$preparedRow['cctv_id']) {
                    $validator->errors()->add('cctv_name', 'CCTV not found');
                }
            });

            $data = Arr::only($preparedRow, $this->getHeader());
            $data['cctv_id'] = $preparedRow['cctv_id'] ?? null;

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
