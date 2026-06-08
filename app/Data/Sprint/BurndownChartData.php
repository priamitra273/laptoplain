<?php

namespace App\Data\Sprint;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BurndownChartData extends Data
{
    public function __construct(
        public string $date,
        public string $label,
        public int $total_plan,
        public int $total_actual,
    ) {}
}
