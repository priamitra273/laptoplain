<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        $allowedRoles = ["super-admin-admin", "watcher-admin"];
        $roles = $user->getRoleNames();

        if ($roles->intersect($allowedRoles)->isNotEmpty()) {
            return true;
        }
        
        $isMember = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', $user->id)
            ->isNotEmpty();
        return $isMember;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $allowedRoles = ["super-admin-admin", "admin-admin"];
        $roles = $user->getRoleNames();

        return $roles->intersect($allowedRoles)->isNotEmpty();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        $allowedRoles = ["super-admin-admin", "admin-admin"];
        $roles = $user->getRoleNames();

        if ($roles->intersect($allowedRoles)->isNotEmpty()) {
            return true;
        }
        
        $isMember = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', $user->id)
            ->isNotEmpty();
        return $isMember;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        $allowedRoles = ["super-admin-admin", "admin-admin"];
        $roles = $user->getRoleNames();

        if ($roles->intersect($allowedRoles)->isNotEmpty()) {
            return true;
        }

        $isOwner = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', $user->id)
            ->where('role.name', 'Owner')
            ->isNotEmpty();
        return $isOwner;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
