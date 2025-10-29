<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Project::all();
    }

    /**
     * @param Project $project
     * @return array
     */
    public function map($project): array
    {
        return [
            $project->name,
            $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('n/j/Y') : '',
            $project->finish_date ? \Carbon\Carbon::parse($project->finish_date)->format('n/j/Y') : '',
            $project->plan_site,
            $project->plan_cctv,
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Name',
            'Start Date',
            'Finish Date',
            'Plan Site',
            'Plan CCTV',
        ];
    }
}