<?php

namespace App\Exports;

use App\Models\Site;
use App\Models\Cctv;
use App\Models\Device;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class PreconfigExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Cctv::with(['site.project', 'site.regency', 'device'])->get();
    }

    /**
     * @param Site $site
     * @return array
     */
    public function map($cctv): array
    {
        $site = $cctv->site;
        $device = $cctv->device;

        return [
            $site->project->name ?? '',
            $site->regency->name ?? '',
            $site->site_id,
            $site->site_name,
            $site->latitude,
            $site->longitude,
            $cctv->name ?? '',
            $device->mac_address ?? '',
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Project Name',
            'Regency Name',
            'Site ID',
            'Site Name',
            'Latitude',
            'Longitude',
            'CCTV Name',
            'MAC Address',
        ];
    }
}
