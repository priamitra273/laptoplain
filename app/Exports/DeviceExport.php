<?php

namespace App\Exports;

use App\Models\Device;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DeviceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Device::all();
    }

    /**
     * @param Device $device
     * @return array
     */
    public function map($device): array
    {
        return [
            $device->mac_address,
            $device->ip_dhcp,
            $device->ip_static ?? '',
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'MAC Address',
            'IP DHCP',
            'IP Static',
        ];
    }
}