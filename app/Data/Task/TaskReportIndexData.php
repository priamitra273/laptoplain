<?php

namespace App\Data\Task;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\CursorPaginatedDataCollection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskReportIndexData extends Data
{
    /**
     * @param  DataCollection|PaginatedDataCollection|CursorPaginatedDataCollection<TaskReportData>  $tasks
     * @param  array<string, array<FilterOptionData>>  $filterOptions
     */
    public function __construct(
        public DataCollection|PaginatedDataCollection|CursorPaginatedDataCollection|LengthAwarePaginator $tasks,
        public array $filters,
        public array $filterOptions,
    ) {}
}
