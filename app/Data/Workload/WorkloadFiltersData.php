<?php

namespace App\Data\Workload;

use Spatie\LaravelData\Data;

class WorkloadFiltersData extends Data
{
    /**
     * Kolom yang boleh dipakai untuk mengurutkan, dipetakan ke kolom SQL-nya.
     * Whitelist, bukan validasi: nilai dari request tidak pernah masuk ke query.
     *
     * @var array<string, string>
     */
    public const SORTABLE_COLUMNS = [
        'name' => 'users.name',
        'total_tasks' => 'w.total_tasks',
        'remaining_work_percent' => 'w.remaining_work_percent',
    ];

    public const DEFAULT_SORT = 'remaining_work_percent';

    public function __construct(
        public ?array $names = null,
        public ?array $workload_statuses = null,
        public ?string $search = null,
        public int $per_page = 50,
        public string $sort = self::DEFAULT_SORT,
        public string $direction = 'desc',
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            names: self::normalizeArray($request->input('names')),
            workload_statuses: self::normalizeArray($request->input('workload_statuses')),
            search: $request->input('search'),
            per_page: (int) $request->input('per_page', 50),
            sort: self::normalizeSort($request->input('sort')),
            direction: $request->input('direction') === 'asc' ? 'asc' : 'desc',
        );
    }

    private static function normalizeSort($value): string
    {
        return array_key_exists((string) $value, self::SORTABLE_COLUMNS) ? (string) $value : self::DEFAULT_SORT;
    }

    private static function normalizeArray($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        return is_array($value) ? $value : explode(',', $value);
    }
}
