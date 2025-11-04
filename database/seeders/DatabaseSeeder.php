<?php

namespace Database\Seeders;

use App\Models\SiteStatus;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Admin',
        //     'email' => 'admin@smarteye.test',
        //     'password' => Hash::make('Admin123$')
        // ]);

        $this->call([
            TeamSeeder::class,
            MenuSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            MsProjectPrioritySeeder::class,
            MsProjectStatusSeeder::class,
            MsProjectRoleSeeder::class,
            MsTaskPrioritySeeder::class,
            MsTaskStatusSeeder::class,
            MsTaskTypeSeeder::class,
        ]);
    }
}
