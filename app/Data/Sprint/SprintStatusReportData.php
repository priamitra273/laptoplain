<?php

namespace App\Data\Sprint;

use App\Data\Task\TaskData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SprintStatusReportData extends Data
{
    public function __construct(
        /** @var TaskData[] */
        public DataCollection $completed_tasks,

        /** @var TaskData[] */
        public DataCollection $incomplete_tasks,
    ) {}
}
