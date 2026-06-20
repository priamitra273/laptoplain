<?php

namespace App\Observers;

use App\Models\ProjectMember;
use App\Repositories\ProjectRepository;

class ProjectMemberObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function created(ProjectMember $member): void
    {
        $this->projects->forgetShell($member->project_id);
    }

    public function updated(ProjectMember $member): void
    {
        $this->projects->forgetShell($member->project_id);
    }

    public function deleted(ProjectMember $member): void
    {
        $this->projects->forgetShell($member->project_id);
    }

    public function restored(ProjectMember $member): void
    {
        $this->projects->forgetShell($member->project_id);
    }
}
