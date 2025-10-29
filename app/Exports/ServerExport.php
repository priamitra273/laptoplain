<?php

namespace App\Exports;

use App\Models\AnalyticServer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ServerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return AnalyticServer::with([
            'cpu', 'gpu', 'ram', 'ssd', 'motherboard', 'nic', 'psu', 'lc'
        ])->get();
    }

    /**
     * @param AnalyticServer $server
     * @return array
     */
    public function map($server): array
    {
        return [
            $server->ip_address,
            $server->lisence,
            $server->is_alive ? 'TRUE' : 'FALSE',
            $server->is_active ? 'TRUE' : 'FALSE',
            $server->cpu->serial_number ?? '',
            $server->gpu->serial_number ?? '',
            $server->ram->serial_number ?? '',
            $server->ssd->serial_number ?? '',
            $server->mobo->serial_number ?? '',
            $server->nic->serial_number ?? '',
            $server->psu->serial_number ?? '',
            $server->lc->serial_number ?? '',
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'IP Address',
            'License',
            'Is Alive',
            'Is Active',
            'CPU SN',
            'GPU SN',
            'RAM SN',
            'SSD SN',
            'MOBO SN',
            'NIC SN',
            'PSU SN',
            'LC SN',
        ];
    }
}