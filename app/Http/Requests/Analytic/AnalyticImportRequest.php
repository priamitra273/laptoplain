<?php

namespace App\Http\Requests\Analytic;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticImportRequest extends FormRequest
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
        $rules = [
            'analytics.*.cctv_name' => 'required|string|exists:cctvs,name',
            'analytics.*.cctv_id' => 'nullable|integer|exists:cctvs,id',
            'analytics.*.link_embed_bb' => 'nullable|url',
            'analytics.*.department_name' => 'required|string|exists:departments,name',
            'analytics.*.categories_name' => 'required|string|exists:analytic_categories,name',
            'analytics.*.server' => 'required|ip|exists:analytic_servers,ip_address',
            'analytics.*.polygon' => 'nullable|array',
            'analytics.*.threshold' => 'nullable|array',
        ];

        $analytics = $this->input('analytics', []);
        foreach ($analytics as $index => $analytic) {
            if (isset($analytic['categories_name'])) {
                $categoryName = $analytic['categories_name'];
                
                if ($categoryName === 'Crowd Detection') {
                    $rules["analytics.{$index}.threshold.people"] = 'required|integer|min:1';
                    $rules["analytics.{$index}.threshold.vehicle"] = 'required|integer|min:1';
                    $rules["analytics.{$index}.threshold.street_vendor"] = 'required|integer|min:1';
                } elseif (in_array($categoryName, ['Water Level', 'Water Surface'])) {
                    $rules["analytics.{$index}.threshold.level_1"] = 'required|numeric|min:0';
                    $rules["analytics.{$index}.threshold.level_2"] = 'required|numeric|min:0';
                    $rules["analytics.{$index}.threshold.level_3"] = 'required|numeric|min:0';
                }
            }
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $analytics = $this->input('analytics', []);
        
        foreach ($analytics as $index => $analytic) {
            if (isset($analytic['categories_name']) && isset($analytic['threshold'])) {
                $analytics[$index]['threshold'] = $this->formatThreshold($analytic['categories_name'], $analytic['threshold']);
            }
        }
        
        $this->merge(['analytics' => $analytics]);
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
}