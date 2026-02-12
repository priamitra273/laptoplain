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
                'score' => 0,
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
                'score' => 50,
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
                'score' => 100,
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
                'score' => 100,
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
                'score' => 0,
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
