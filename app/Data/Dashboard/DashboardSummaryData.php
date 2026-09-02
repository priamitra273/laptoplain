<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class DashboardSummaryData extends Data
{
    public function __construct(
        public int $running_projects,
        public int $tasks_due_this_week,
        public int $attention_items,
    ) {}
}
