<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsProjectPriority;
use Illuminate\Support\Facades\DB;

class MsProjectPrioritySeeder extends Seeder
{
    public function run(): void
    {
        //Kosongkan Tabel jika ada data
        DB::table('ms_project_priority')->truncate();

        $priorities =
        [
            [
                'name' => 'Low',
                'severity' => 'Low impact, not urgent',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Medium',
                'severity' => 'Moderate impact, needs attention',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'High',
                'severity' => 'High impact, requires immediate action',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Critical',
                'severity' => 'Critical impact, urgent resolution needed',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        MsProjectPriority::insert($priorities);
    }
}