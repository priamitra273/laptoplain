<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class ActivityGraphData extends Data
{
    /**
     * @param  array<int, array{date: string, count: int}>  $values
     */
    public function __construct(
        public array $values,
        public int $total_events,
        public int $weeks,
        public ?string $busiest_date,
        public int $busiest_count,
        public int $current_streak,
        public int $weekly_average,
    ) {}
}
