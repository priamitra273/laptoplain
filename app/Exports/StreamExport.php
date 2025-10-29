<?php

namespace App\Exports;

use App\Models\Cctv;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StreamExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Cctv::all();
    }

    /**
     * @param mixed $cctv
     * @return array
     */
    public function map($cctv): array
    {
        return [
            $cctv->name,
            $cctv->ip_flussonic,
            $cctv->ip_static,
            $cctv->link_embed,
            $cctv->link_embed_nonrelay,
            $cctv->link_rtsp,
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'CCTV Name',
            'IP Flussonic',
            'IP Static',
            'Link Embed',
            'Link Embed Nonrelay',
            'Link RTSP',
        ];
    }
}