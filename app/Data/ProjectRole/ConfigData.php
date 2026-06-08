<?php

namespace App\Data\ProjectRole;

use App\Enums\ProjectRolePermission;
use App\Enums\TaskField;
use App\Facades\Sqids;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class ConfigData extends Data
{
    public function __construct(
        /** @var array<ProjectRolePermission> */
        public array $task,

        /** @var array<ProjectRolePermission> */
        public array $sprint,

        /** @var array<ProjectRolePermission> */
        public array $project_member,

        /** @var array<int> */
        #[TypeScriptType('string[]')]
        public array $allow_task_status,

        /** @var array<TaskField> */
        public array $allow_update_task_fields,
    ) {}

    public function toResponse($request = null): array
    {
        return [
            'task' => $this->task,
            'sprint' => $this->sprint,
            'project_member' => $this->project_member,
            'allow_task_status' => array_map(fn ($id) => Sqids::encode($id), $this->allow_task_status),
            'allow_update_task_fields' => $this->allow_update_task_fields,
        ];
    }
}
