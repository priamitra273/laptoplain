<?php

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TagData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $severity,
    ) {
    }
}
