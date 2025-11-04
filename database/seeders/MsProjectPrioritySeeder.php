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
                'severity' => 'info',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Medium',
                'severity' => 'warning',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'High',
                'severity' => 'danger',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Critical',
                'severity' => 'contrast',
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