<?php

namespace Database\Seeders;

use App\Models\AnalyticCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnalyticCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AnalyticCategory::create(['type' => 'A', 'name' => 'Object Counting']);
        AnalyticCategory::create(['type' => 'A', 'name' => 'Water Level', 'has_polygon' => true, 'has_threshold' => true]);
        AnalyticCategory::create(['type' => 'A', 'name' => 'Water Surface', 'has_polygon' => true, 'has_threshold' => true]);
        AnalyticCategory::create(['type' => 'A', 'name' => 'Crowd Detection', 'has_polygon' => true, 'has_threshold' => true]);
        AnalyticCategory::create(['type' => 'B', 'name' => 'LPR']);
        AnalyticCategory::create(['type' => 'C', 'name' => 'Taman']);
    }
}
