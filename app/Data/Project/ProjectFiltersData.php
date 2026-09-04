<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;

class ProjectFiltersData extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $status_id = null,
        public ?array $priority_ids = null,
        public ?string $start_date = null,
        public ?string $due_date = null,
        public int $progress_min = 0,
        public int $progress_max = 100,
        public int $per_page = 10,
        public int $page = 1,
        public string $sort = 'id',
        public string $direction = 'desc',
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            search: $request->input('search'),
            status_id: $request->input('status_id'),
            priority_ids: self::normalizeArray($request->input('priority_ids')),
            start_date: $request->input('start_date'),
            due_date: $request->input('due_date'),
            progress_min: (int) $request->input('progress_min', 0),
            progress_max: (int) $request->input('progress_max', 100),
            per_page: (int) $request->input('per_page', 10),
            page: (int) $request->input('page', 1),
            sort: (string) $request->input('sort', 'id'),
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
