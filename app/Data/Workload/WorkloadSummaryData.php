<?php

namespace App\Data\Workload;

use Spatie\LaravelData\Data;

class WorkloadSummaryData extends Data
{
    public function __construct(
        public int $total_users,
        public int $free,
        public int $light,
        public int $moderate,
        public int $busy,
    ) {
    }
}
