<?php

namespace App\Data\Project\Lazy;

use App\Data\UserData;
use App\Models\ProjectMember;
use Spatie\LaravelData\Data;

class ShellMemberData extends Data
{
    public function __construct(
        public int $id,
        public UserData $user,
    ) {}

    public static function fromModel(ProjectMember $member): self
    {
        return new self(
            id: (int) $member->id,
            user: UserData::fromModel($member->user),
        );
    }
}
