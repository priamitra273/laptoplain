<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Epic',  'icon' => 'i-lucide-zap', 'severity' => 'contrast'],
            ['name' => 'Story', 'icon' => 'i-lucide-bookmark', 'severity' => 'success'],
            ['name' => 'Issue', 'icon' => 'i-lucide-circle-alert', 'severity' => 'warn'],
            ['name' => 'Task', 'icon' => 'i-lucide-square-check', 'severity' => 'info'],
            ['name' => 'Bug', 'icon' => 'i-lucide-triangle-alert', 'severity' => 'danger'],
        ];

        foreach ($categories as $category) {
            DB::table('task_categories')->updateOrInsert(
                ['name' => $category['name']],
                [...$category, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
