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
                'severity' => 'secondary',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'In Progress',
                'severity' => 'primary',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'On Hold',
                'severity' => 'warn',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Completed',
                'severity' => 'success',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cancelled',
                'severity' => 'danger',
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
