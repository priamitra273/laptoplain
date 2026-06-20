<?php

namespace App\Observers;

use App\Models\MsProjectStatus;
use App\Repositories\ProjectRepository;

class MsProjectStatusObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function updated(MsProjectStatus $status): void
    {
        if ($status->wasChanged(['name', 'severity'])) {
            $this->projects->flushShells();
        }
    }

    public function deleted(MsProjectStatus $status): void
    {
        $this->projects->flushShells();
    }
}
