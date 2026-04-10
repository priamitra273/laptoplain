<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectRoleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
