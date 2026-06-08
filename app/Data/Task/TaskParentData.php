<?php

namespace App\Data\Task;

use App\Models\Task;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class TaskParentData extends Data
{
    public function __construct(
        #[TypeScriptType('string')]
        public int $id,
        public string $key,
        public string $title,
        public ?TaskCategoryData $category,
    ) {}

    public static function fromModel(Task $model): static
    {
        return new static(
            id: $model->id,
            key: $model->key,
            title: $model->title,
            category: $model->task_category_id ? new TaskCategoryData(
                id: $model->task_category_id,
                name: $model->task_category_name,
                icon: $model->task_category_icon,
                severity: $model->task_category_severity,
            ) : null,
        );
    }
}
