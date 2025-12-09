<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsTaskStatus;
use Illuminate\Support\Facades\DB;

class MsTaskStatusSeeder extends Seeder
{
    
    public function run(): void
    {

        DB::table('ms_task_statuses')->truncate();

        $statuses = [
            [
                'name' => 'To Do',
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
                'name' => 'In Review',
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
                'name' => 'Blocked',
                'severity' => 'danger',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        MsTaskStatus::insert($statuses);
    }
}
