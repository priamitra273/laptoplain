<?php

namespace App\Observers;

use App\Models\MsProjectRole;
use App\Repositories\ProjectRepository;

class MsProjectRoleObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function updated(MsProjectRole $role): void
    {
        if ($role->wasChanged(['name', 'config'])) {
            $this->projects->flushShells();
        }
    }

    public function deleted(MsProjectRole $role): void
    {
        $this->projects->flushShells();
    }
}
