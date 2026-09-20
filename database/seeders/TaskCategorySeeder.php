<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Epic',  'icon' => 'Zap', 'severity' => 'contrast'],
            ['name' => 'Story', 'icon' => 'Bookmark', 'severity' => 'success'],
            ['name' => 'Issue', 'icon' => 'CircleAlert', 'severity' => 'warn'],
            ['name' => 'Task', 'icon' => 'SquareCheck', 'severity' => 'info'],
            ['name' => 'Bug', 'icon' => 'TriangleAlert', 'severity' => 'danger'],
        ];

        foreach ($categories as $category) {
            DB::table('task_categories')->updateOrInsert(
                ['name' => $category['name']],
                [...$category, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
