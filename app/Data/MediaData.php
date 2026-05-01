<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaData extends Data
{
    public function __construct(
        public string $uuid,
        public string $file_name,
        public int $size,
        public string $mime_type,
        public string $url,
        public Carbon $created_at,
        public Carbon $updated_at,
    ) {}

    public static function fromModel(mixed $model): static
    {
        return new static(
            $model->uuid,
            $model->file_name,
            $model->size,
            $model->mime_type,
            $model->getFullUrl(),
            $model->created_at,
            $model->updated_at,
        );
    }
}
