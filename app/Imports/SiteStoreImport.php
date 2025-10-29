<?php

namespace App\Imports;

use App\Models\Regency;
use App\Models\Project;
use App\Models\SiteStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class SiteStoreImport implements ToArray, WithValidation, WithHeadingRow
{
    use Importable;

    public function array(array $array)
    {
        return $this->getValidatedData($array);
    }

    public function rules(): array
    {
        return [
            'site_category' => 'required|string|in:New,Replacement',

            'replacement_to' => [
                'nullable',
                'required_if:site_category,Replacement',
                'integer',
                Rule::exists('sites', 'id')->where('site_status_id', 4)->withoutTrashed(),
            ],

            // Changed to accept names instead of IDs
            'regency_name' => 'required|string|exists:regencies,name',
            'project_name' => 'required|string|exists:projects,name',
            'site_id' => 'required|integer|min_digits:6|unique:sites,site_id',
            'site_name' => 'required|string|max:255',
            'latitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'longitude' => ['required', Rule::numeric()->decimal(6, 18)],
            'site_status_name' => 'required|string|exists:site_statuses,name',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Transform names to IDs for database storage
        if (isset($data['regency_name'])) {
            $regency = Regency::where('name', $data['regency_name'])->first();
            $data['regency_id'] = $regency ? $regency->id : null;
        }

        if (isset($data['project_name'])) {
            $project = Project::where('name', $data['project_name'])->first();
            $data['project_id'] = $project ? $project->id : null;
        }

        if (isset($data['site_status_name'])) {
            $siteStatus = SiteStatus::where('name', $data['site_status_name'])->first();
            $data['site_status_id'] = $siteStatus ? $siteStatus->id : null;
        }

        return $data;
    }

    public function getValidatedData(array $array)
    {
        $safe_data = [];

        foreach ($array as $row) {
            // Prepare the row data (convert names to IDs)
            $preparedRow = $this->prepareForValidation($row, 0);
            
            $validator = Validator::make($preparedRow, $this->rules());

            // Get both the original names and converted IDs
            $data = Arr::only($validator->getData(), $this->getHeader());
            
            // Add the converted IDs to the data
            if (isset($preparedRow['regency_id'])) {
                $data['regency_id'] = $preparedRow['regency_id'];
            }
            if (isset($preparedRow['project_id'])) {
                $data['project_id'] = $preparedRow['project_id'];
            }
            if (isset($preparedRow['site_status_id'])) {
                $data['site_status_id'] = $preparedRow['site_status_id'];
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