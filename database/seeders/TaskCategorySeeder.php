<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Epic',  'icon' => '⚡', 'severity' => 1],
            ['name' => 'Story', 'icon' => '📖', 'severity' => 2],
            ['name' => 'Issue', 'icon' => '🔧', 'severity' => 3],
        ];

        foreach ($categories as $category) {
            DB::table('task_categories')->updateOrInsert(
                ['name' => $category['name']],
                [...$category, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
