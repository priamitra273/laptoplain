<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsProjectRole;
use Illuminate\Support\Facades\DB;

class MsProjectRoleSeeder extends Seeder
{
    public function run(): void
    {
        
        DB::table('ms_project_roles')->truncate();

        
        $roles = [
            [
                'name' => 'Owner',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Member',
                'owned_id' => 1,
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        MsProjectRole::insert($roles);
    }
}
