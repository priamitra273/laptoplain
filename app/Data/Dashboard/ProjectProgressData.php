<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class ProjectProgressData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $emoji,
        public int $total_tasks,
        public int $done_tasks,
        public int $progress_percent,
        public ?string $due_date,
        public ?string $status_name,
        public ?string $status_severity,
    ) {}
}
