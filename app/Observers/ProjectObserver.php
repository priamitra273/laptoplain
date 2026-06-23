<?php

namespace App\Observers;

use App\Models\Project;
use App\Repositories\ProjectRepository;

class ProjectObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function updated(Project $project): void
    {
        $this->projects->forgetShell($project->id);
    }

    public function deleted(Project $project): void
    {
        $this->projects->forgetShell($project->id);
    }
}
