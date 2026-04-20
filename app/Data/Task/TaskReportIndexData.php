<?php

namespace App\Data\Task;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskReportIndexData extends Data
{
    public function __construct(
        #[DataCollectionOf(TaskReportData::class)]
        public DataCollection $tasks,
        public array $filters,
        public array $filterOptions,
    ) {}
}
