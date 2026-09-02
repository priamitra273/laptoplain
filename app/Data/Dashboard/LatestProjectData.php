<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class LatestProjectData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $created_at,
        public int $members_count,
        public ?string $status_name,
        public ?string $status_severity,
    ) {}
}
