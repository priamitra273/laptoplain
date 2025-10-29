<?php

namespace App\Exports;

use App\Models\Hardware;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HardwareExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Hardware::with(['brand', 'component', 'status'])
            ->select('*')
            ->addSelect(DB::raw('ROW_NUMBER() OVER (ORDER BY id) as no'))
            ->get();
    }

    /**
     * @param Hardware $hardware
     * @return array
     */
    public function map($hardware): array
    {
        return [
            $hardware->no,
            $hardware->uuid,
            $hardware->po_number,
            $hardware->serial_number,
            $hardware->category ?? $hardware->component->name,
            $hardware->brand?->name,
            $hardware->model,
            $hardware->remarks ?? '',
            $hardware->status->name,
            Date::dateTimeToExcel($hardware->created_at->timezone('Asia/Jakarta'))
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'UUID',
            'PO Number',
            'Serial Number',
            'Category',
            'Brand',
            'Model',
            'Remarks',
            'Status',
            'Created At'
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_DATE_DATETIME
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // force the uuid column into monospace style
            'B' => [
                'font' => [
                    'name' => 'Consolas'
                ]
            ]
        ];
    }
}
