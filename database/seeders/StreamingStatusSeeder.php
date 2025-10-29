<?php

namespace Database\Seeders;

use App\Models\StreamingStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StreamingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StreamingStatus::create(['name' => 'OPEN']);
        StreamingStatus::create(['name' => 'PROCESS']);
        StreamingStatus::create(['name' => 'CANCEL']);
        StreamingStatus::create(['name' => 'COMPLETE']);
    }
}
