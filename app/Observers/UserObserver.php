<?php

namespace App\Observers;

use App\Models\User;
use App\Repositories\ProjectRepository;

class UserObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function updated(User $user): void
    {
        if ($user->wasChanged(['name', 'email', 'is_active'])) {
            $this->projects->flushShells();
        }
    }

    public function deleted(User $user): void
    {
        $this->projects->flushShells();
    }
}
