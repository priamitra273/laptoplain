<?php

namespace App\Imports;

use App\Models\Cctv;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AnalyticStoreImport implements ToArray, WithValidation, WithHeadingRow
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
            'link_embed_bb' => 'nullable|url',
            'department_name' => 'required|string|exists:departments,name',
            'categories_name' => 'required|string|exists:analytic_categories,name',
            'server' => 'required|ip|exists:analytic_servers,ip_address',
            'polygon' => 'nullable|array',
            'threshold' => 'nullable|array',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        if (isset($data['cctv_name'])) {
            $cctv = Cctv::where('name', $data['cctv_name'])->first();
            $data['cctv_id'] = $cctv ? $cctv->id : null;
        }

        if (isset($data['polygon']) && is_string($data['polygon'])) {
            try {
                $data['polygon'] = json_decode($data['polygon'], true);
            } catch (\Exception $e) {
                $data['polygon'] = null;
            }
        }

        if (isset($data['threshold']) && is_string($data['threshold'])) {
            try {
                $data['threshold'] = json_decode($data['threshold'], true);
            } catch (\Exception $e) {
                $data['threshold'] = null;
            }
        }

        if (isset($data['categories_name']) && isset($data['threshold'])) {
            $data['threshold'] = $this->formatThreshold($data['categories_name'], $data['threshold']);
        }

        return $data;
    }

    private function formatThreshold($categoryName, $threshold)
    {
        if ($categoryName === 'Crowd Detection') {
            if (is_array($threshold)) {
                if (isset($threshold['people']) || isset($threshold['vehicle']) || isset($threshold['street_vendor'])) {
                    return [
                        'people' => $threshold['people'] ?? 10,
                        'vehicle' => $threshold['vehicle'] ?? 10,
                        'street_vendor' => $threshold['street_vendor'] ?? 10
                    ];
                } else if (count($threshold) >= 3) {
                    return [
                        'people' => $threshold[0] ?? 10,
                        'vehicle' => $threshold[1] ?? 10,
                        'street_vendor' => $threshold[2] ?? 10
                    ];
                }
            }
            return ['people' => 10, 'vehicle' => 10, 'street_vendor' => 10];
        }

        if (in_array($categoryName, ['Water Level', 'Water Surface'])) {
            if (is_array($threshold)) {
                if (isset($threshold['level_1']) || isset($threshold['level_2']) || isset($threshold['level_3'])) {
                    return [
                        'level_1' => $threshold['level_1'] ?? 0,
                        'level_2' => $threshold['level_2'] ?? 0,
                        'level_3' => $threshold['level_3'] ?? 0
                    ];
                } else if (count($threshold) >= 3) {
                    return [
                        'level_1' => $threshold[0] ?? 0,
                        'level_2' => $threshold[1] ?? 0,
                        'level_3' => $threshold[2] ?? 0
                    ];
                }
            }
            return ['level_1' => 0, 'level_2' => 0, 'level_3' => 0];
        }

        return $threshold;
    }

    public function getValidatedData(array $array)
    {
        $safe_data = [];

        foreach ($array as $index => $row) {
            $preparedRow = $this->prepareForValidation($row, $index);
            $rules = $this->rules();

            if (isset($preparedRow['categories_name'])) {
                $categoryName = $preparedRow['categories_name'];

                if (in_array($categoryName, ['Water Level', 'Water Surface', 'Crowd Detection'])) {
                    $rules['polygon'] = 'required|array';
                    $rules['threshold'] = 'required|array';
                    
                    if ($categoryName === 'Crowd Detection') {
                        $rules['threshold.people'] = 'required|integer|min:1';
                        $rules['threshold.vehicle'] = 'required|integer|min:1';
                        $rules['threshold.street_vendor'] = 'required|integer|min:1';
                    } elseif (in_array($categoryName, ['Water Level', 'Water Surface'])) {
                        $rules['threshold.level_1'] = 'required|numeric|min:0';
                        $rules['threshold.level_2'] = 'required|numeric|min:0';
                        $rules['threshold.level_3'] = 'required|numeric|min:0';
                    }
                }
            }

            $validator = Validator::make($preparedRow, $rules);

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
