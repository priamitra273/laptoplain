<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MsSprintStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Planning',  'severity' => 1],
            ['name' => 'Active',    'severity' => 2],
            ['name' => 'Completed', 'severity' => 3],
        ];

        foreach ($statuses as $status) {
            DB::table('ms_sprint_statuses')->updateOrInsert(
                ['name' => $status['name']],
                [...$status, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
