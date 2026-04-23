<?php

namespace App\Data\Sprint;

use Spatie\LaravelData\Data;

class BurndownChartData extends Data
{
    public function __construct(
        public string $date,
        public string $label,
        public int $total_plan,
        public int $total_actual,
    ) {}
}
