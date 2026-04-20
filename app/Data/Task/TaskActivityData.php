<?php

namespace App\Data\Task;

use App\Data\UserData;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class TaskActivityData extends Data
{
    public function __construct(
        #[TypeScriptType('string')]
        public int $id,
        public string $event,
        public ?UserData $causer,

        /** @var TaskActivityFieldData[] */
        public array $changed_fields,

        #[TypeScriptType('string')]
        public Carbon $created_at,
    ) {}
}
