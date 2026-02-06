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
            'name' => 'superadmin',
            'guard_name' => 'web',
            'label' => 'Super Admin',
            'is_active' => true
        ]);

        $role->syncPermissions(Permission::all());

        Role::create([
            'team_id' => 1,
            'name' => 'admin',
            'guard_name' => 'web',
            'label' => 'Admin',
            'is_active' => true
        ]);
        
        Role::create([
            'team_id' => 1,
            'name' => 'watcher',
            'guard_name' => 'web',
            'label' => 'Watcher',
            'is_active' => true
        ]);

        Role::create([
            'team_id' => 2,
            'name' => 'user',
            'guard_name' => 'web',
            'label' => 'User',
            'is_active' => true
        ]);
    }
}
