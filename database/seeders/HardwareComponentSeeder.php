<?php

namespace Database\Seeders;

use App\Models\HardwareComponent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HardwareComponent::create(['code' => 'CPU', 'name' => 'CPU']);
        HardwareComponent::create(['code' => 'GPU', 'name' => 'GPU']);
        HardwareComponent::create(['code' => 'RAM', 'name' => 'RAM']);
        HardwareComponent::create(['code' => 'SSD', 'name' => 'SSD']);
        HardwareComponent::create(['code' => 'MOBO', 'name' => 'Motherboard']);
        HardwareComponent::create(['code' => 'NIC', 'name' => 'Network Interface Card']);
        HardwareComponent::create(['code' => 'PSU', 'name' => 'Power Supply Unit']);
        HardwareComponent::create(['code' => 'LC', 'name' => 'Liquid Cooling']);
    }
}
