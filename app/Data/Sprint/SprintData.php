<?php

namespace App\Data\Sprint;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class SprintData extends Data
{
    public function __construct(
        #[TypeScriptType('string')]
        public int $id,

        #[TypeScriptType('string')]
        public int $project_id,

        public string $name,
        public ?string $goal,
        public ?string $duration,

        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        #[TypeScriptType('string')]
        public ?Carbon $start_date,

        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        #[TypeScriptType('string')]
        public ?Carbon $end_date,

        public int $order,
        public ?string $retrospective,
        public ?StatusData $status,
        public Carbon $created_at,
        public ?Carbon $updated_at,
    ) {}
}
