<?php

namespace App\Observers;

use App\Models\MsProjectPriority;
use App\Repositories\ProjectRepository;

class MsProjectPriorityObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function updated(MsProjectPriority $priority): void
    {
        if ($priority->wasChanged(['name', 'severity'])) {
            $this->projects->flushShells();
        }
    }

    public function deleted(MsProjectPriority $priority): void
    {
        $this->projects->flushShells();
    }
}
