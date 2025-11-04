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
                'severity' => 'Task belum dimulai',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'In Progress',
                'severity' => 'Task sedang dikerjakan',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'In Review',
                'severity' => 'Task sudah selesai dan menunggu review',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Completed',
                'severity' => 'Task telah selesai dan disetujui',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Blocked',
                'severity' => 'Task terhambat oleh kendala lain',
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
