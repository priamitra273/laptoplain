<?php

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FilterOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $severity = null,
        public ?string $avatar_url = null,
    ) {
    }
}
