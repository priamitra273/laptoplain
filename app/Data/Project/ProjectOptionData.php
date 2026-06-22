<?php

namespace App\Data\Project;

use App\Models\Project;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

/**
 * Minimal project shape for filter dropdowns and the nested project on a task card.
 */
#[TypeScript]
class ProjectOptionData extends Data
{
    public function __construct(
        #[TypeScriptType('string')] public int $id,
        public string $title,
    ) {}

    public static function fromModel(Project $project): self
    {
        return new self(
            id: (int) $project->id,
            title: (string) $project->title,
        );
    }
}
