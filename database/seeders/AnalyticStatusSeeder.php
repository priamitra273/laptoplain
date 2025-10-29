<?php

namespace Database\Seeders;

use App\Models\AnalyticStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnalyticStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AnalyticStatus::create(['name' => 'OPEN']);
        AnalyticStatus::create(['name' => 'DRAFT']);
        AnalyticStatus::create(['name' => 'CANCEL']);
        AnalyticStatus::create(['name' => 'COMPLETE']);
    }
}
