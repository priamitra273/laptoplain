<?php

namespace App\Data\Workload;

use Spatie\LaravelData\Data;

class WorkloadOptionsData extends Data
{
    public function __construct(
        /** @var array<array{id: string, name: string, avatar_url: ?string}> */
        public array $users,
        /** @var array<array{id: int, name: string, severity: string}> */
        public array $workload_statuses,
    ) {
    }
}
