<?php

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $icon,
        public ?string $severity,
    ) {
    }
}
