<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('12345678')
        ]);
        $superadmin->assignRole('super-admin-admin');
        
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678')
        ]);
        $admin->assignRole('admin-admin');
        
        $watcher = User::factory()->create([
            'name' => 'Watcher',
            'email' => 'watcher@example.com',
            'password' => Hash::make('12345678')
        ]);
        $watcher->assignRole('watcher-admin');
    }
}
