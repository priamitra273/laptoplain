<?php

namespace Database\Seeders;

use App\Models\SiteStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'OPEN',
            'PROGESS',
            'RELOCATION',
            'DISMANTLE',
            'COMPLETE'
        ];

        foreach ($names as $value) {
            SiteStatus::create(['name' => $value]);
        }
    }
}
