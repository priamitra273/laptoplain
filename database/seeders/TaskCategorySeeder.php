<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Epic',  'icon' => 'pi pi-bolt', 'severity' => 'primary'],
            ['name' => 'Story', 'icon' => 'pi pi-bookmark', 'severity' => 'success'],
            ['name' => 'Issue', 'icon' => 'pi pi-info-circle', 'severity' => 'warn'],
            ['name' => 'Task', 'icon' => 'pi pi-check-square', 'severity' => 'info'],
            ['name' => 'Bug', 'icon' => 'pi pi-exclamation-triangle', 'severity' => 'danger'],
        ];

        foreach ($categories as $category) {
            DB::table('task_categories')->updateOrInsert(
                ['name' => $category['name']],
                [...$category, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
