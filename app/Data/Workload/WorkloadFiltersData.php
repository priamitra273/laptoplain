<?php

namespace App\Data\Workload;

use Spatie\LaravelData\Data;

class WorkloadFiltersData extends Data
{
    public function __construct(
        public ?array $names = null,
        public ?array $workload_statuses = null,
        public ?string $search = null,
        public int $per_page = 50,
        public string $sort = 'remaining_work_percent',
        public string $direction = 'desc',
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            names: self::normalizeArray($request->input('names')),
            workload_statuses: self::normalizeArray($request->input('workload_statuses')),
            search: $request->input('search'),
            per_page: (int) $request->input('per_page', 50),
            sort: (string) $request->input('sort', 'remaining_work_percent'),
            direction: (string) $request->input('direction', 'desc'),
        );
    }

    private static function normalizeArray($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        return is_array($value) ? $value : explode(',', $value);
    }
}
