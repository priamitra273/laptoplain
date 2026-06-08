<?php

namespace App\Data\Workload;

use App\Facades\Sqids;
use Spatie\LaravelData\Data;

class WorkloadUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $avatar_url,
        public int $remaining_work_percent,
        public string $workload_status,
        public int $total_tasks,
    ) {
    }

    public static function fromResource($user): self
    {
        return new self(
            id: Sqids::encode($user->id),
            name: $user->name,
            avatar_url: $user->avatar_url ?? null,
            remaining_work_percent: (int) $user->remaining_work_percent,
            workload_status: $user->workload_status,
            total_tasks: (int) $user->total_tasks,
        );
    }
}
