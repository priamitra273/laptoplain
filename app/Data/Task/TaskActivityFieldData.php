<?php

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskActivityFieldData extends Data
{
    public function __construct(
        public string $field,
        public ?string $old_value,
        public ?string $new_value,
        public bool $has_value,
    ) {}
}
