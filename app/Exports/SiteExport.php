<?php

namespace App\Exports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SiteExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Site::with(['regency', 'project', 'status', 'replacement'])
            ->get();
    }

    /**
     * @param Site $site
     * @return array
     */
    public function map($site): array
    {
        return [
            $site->replacement_to ? 'Replacement' : 'New',
            $site->replacement_to,
            $site->regency->name ?? '',
            $site->project->name ?? '',
            $site->site_id,
            $site->site_name,
            $site->latitude,
            $site->longitude,
            $site->status->name ?? '',
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Site Category',
            'Replacement To',
            'Regency Name',
            'Project Name',
            'Site ID',
            'Site Name',
            'Latitude',
            'Longitude',
            'Site Status Name',
        ];
    }
}