<?php

namespace App\Exports;

use App\Models\Cctv;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AnalyticExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Cctv::with(['department', 'analytic_category', 'analytic_server'])->get();
    }

    /**
     * @param mixed $cctv
     * @return array
     */
    public function map($cctv): array
    {
        return [
            $cctv->name,
            $cctv->link_embed_bb,
            $cctv->department->name ?? '',
            $cctv->analyticCategory->name ?? '',
            $cctv->analyticServer->ip_address ?? '',
            is_array($cctv->polygon) ? json_encode($cctv->polygon) : $cctv->polygon,
            is_array($cctv->threshold) ? json_encode($cctv->threshold) : $cctv->threshold,
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'CCTV Name',
            'Link Embed BB',
            'Department Name',
            'Categories Name',
            'Server',
            'Polygon',
            'Threshold',
        ];
    }
}