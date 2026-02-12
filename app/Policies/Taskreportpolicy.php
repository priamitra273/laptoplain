<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view task reports
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']) ||
            $user->roles()
            ->where('name', 'like', 'admin-%')
            ->orWhere('name', 'like', 'superadmin-%')
            ->exists();
    }


    /**
     * Determine if the user can export task reports
     */
    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }
}
