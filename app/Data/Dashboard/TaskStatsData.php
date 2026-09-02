<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class TaskStatsData extends Data
{
    public function __construct(
        public int $total,
        public int $done,
        public int $done_recently,
        public int $in_progress,
        public int $in_progress_assigned_to_me,
        public int $overdue,
        public int $due_soon,
        public int $active,
        public int $progress_percent,
    ) {}
}
