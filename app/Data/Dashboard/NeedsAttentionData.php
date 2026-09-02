<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Data;

class NeedsAttentionData extends Data
{
    /**
     * @param  array<int, AttentionTaskData>  $items
     */
    public function __construct(
        public int $total,
        public array $items,
    ) {}
}
