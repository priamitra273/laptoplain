<?php

namespace App\Data\Task;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskReportIndexData extends Data
{
    public function __construct(
        #[DataCollectionOf(TaskReportData::class)]
        public DataCollection|LengthAwarePaginator $tasks,

        public array $filters,
        public array $filterOptions,
        public array $project_statuses,
    ) {}
}
