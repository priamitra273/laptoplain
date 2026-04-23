<?php

namespace App\Data\Project;

use App\Data\UserData;
use App\Models\ProjectMember;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectMemberData extends Data
{
    public function __construct(
        public int $id,
        public bool $is_active,
        public UserData $user,
        public ProjectRoleData $role,
    ) {
    }

    public static function fromModel(ProjectMember $member): self
    {
        return new self(
            id: $member->id,
            is_active: $member->is_active ?? true,
            user: UserData::fromModel($member->user),
            role: ProjectRoleData::from($member->role),
        );
    }
}
