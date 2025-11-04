<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsProjectStatus;
use Illuminate\Support\Facades\DB;

class MsProjectStatusSeeder extends Seeder
{
    
    public function run(): void
    {

        DB::table('ms_project_statuses')->truncate();

        $statuses = [
            [
                'name' => 'Not Started',
                'severity' => 'Project has not started yet',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'In Progress',
                'severity' => 'Project is currently in progress',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'On Hold',
                'severity' => 'Project temporarily paused',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Completed',
                'severity' => 'Project completed successfully',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cancelled',
                'severity' => 'Project cancelled before completion',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        MsProjectStatus::insert($statuses);
    }
}
