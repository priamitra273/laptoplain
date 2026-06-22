<?php

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class TaskStatusData extends Data
{
    public function __construct(
        #[TypeScriptType('string')]
        public int $id,
        public string $name,
        public ?string $severity,
        public ?float $score,
    ) {}
}
