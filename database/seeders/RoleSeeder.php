<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::create([
            'team_id' => 1,
            'name' => 'admin-it',
            'guard_name' => 'web',
            'label' => 'Admin',
            'is_active' => true
        ]);

        $role->syncPermissions(Permission::all());
    }
}
