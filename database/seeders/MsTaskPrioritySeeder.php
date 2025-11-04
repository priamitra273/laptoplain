<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsTaskPriority;
use Illuminate\Support\Facades\DB;

class MsTaskPrioritySeeder extends Seeder
{
    
    public function run(): void
    {
        
        DB::table('ms_task_priorities')->truncate();

        $priorities = [
            [
                'name' => 'Low',
                'severity' => 'Minor impact, can be handled later',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Medium',
                'severity' => 'Moderate impact, needs attention soon',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'High',
                'severity' => 'Significant impact, prioritize resolution',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Critical',
                'severity' => 'Severe impact, immediate action required',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        MsTaskPriority::insert($priorities);
    }
}
