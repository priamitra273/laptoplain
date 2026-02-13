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
        $superadmin = Role::create([
            'team_id' => 1,
            'name' => 'super-admin-admin',
            'guard_name' => 'web',
            'label' => 'Super Admin',
            'is_active' => true
        ]);

        $superadmin->syncPermissions(Permission::all());

        $admin = Role::create([
            'team_id' => 1,
            'name' => 'admin-admin',
            'guard_name' => 'web',
            'label' => 'Admin',
            'is_active' => true
        ]);

        $admin->syncPermissions([
            'dashboard.read',
        ]);
        
        $watcher = Role::create([
            'team_id' => 1,
            'name' => 'watcher-admin',
            'guard_name' => 'web',
            'label' => 'Watcher',
            'is_active' => true
        ]);

        $watcher->syncPermissions([
            'dashboard.read',
        ]);

        $user = Role::create([
            'team_id' => 2,
            'name' => 'user-user',
            'guard_name' => 'web',
            'label' => 'User',
            'is_active' => true
        ]);

        $user->syncPermissions([
            'dashboard.read',
        ]);
    }
}
