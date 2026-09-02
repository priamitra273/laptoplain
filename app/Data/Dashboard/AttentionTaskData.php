<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class AttentionTaskData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $due_date,
        /** Negatif berarti sudah lewat tenggat. */
        public int $days_remaining,
        public int $open_subtasks,
        public ?string $owner_name,
    ) {}
}
