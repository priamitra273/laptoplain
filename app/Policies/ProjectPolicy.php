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
        $allowedRoles = ["super-admin-", "watcher-"];
        $roles = $user->getRoleNames();

        $hasAllowedRole = $roles->some(function ($role) use ($allowedRoles) {
            foreach ($allowedRoles as $prefix) {
                if (str_starts_with($role, $prefix)) {
                    return true;
                }
            }
            return false;
        });

        if ($hasAllowedRole) {
            return true;
        }
        
        $isMember = $project
            ->projectMembers()
            ->whereNotNull('user_id')
            ->where('user_id', $user->id)
            ->exists();
        return $isMember;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $allowedRoles = ["super-admin-", "admin-"];
        $roles = $user->getRoleNames();

        $hasAllowedRole = $roles->some(function ($role) use ($allowedRoles) {
            foreach ($allowedRoles as $prefix) {
                if (str_starts_with($role, $prefix)) {
                    return true;
                }
            }
            return false;
        });

        return $hasAllowedRole;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        $allowedRoles = ["super-admin-", "admin-"];
        $roles = $user->getRoleNames();

        $hasAllowedRole = $roles->some(function ($role) use ($allowedRoles) {
            foreach ($allowedRoles as $prefix) {
                if (str_starts_with($role, $prefix)) {
                    return true;
                }
            }
            return false;
        });

        if ($hasAllowedRole) {
            return true;
        }
        
        $isMember = $project
            ->projectMembers()
            ->whereNotNull('user_id')
            ->where('user_id', $user->id)
            ->exists();
        return $isMember;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        $allowedRoles = ["super-admin-", "admin-"];
        $roles = $user->getRoleNames();

        $hasAllowedRole = $roles->some(function ($role) use ($allowedRoles) {
            foreach ($allowedRoles as $prefix) {
                if (str_starts_with($role, $prefix)) {
                    return true;
                }
            }
            return false;
        });

        if ($hasAllowedRole) {
            return true;
        }

        $isOwner = $project
            ->projectMembers()
            ->whereNotNull('user_id')
            ->where('user_id', $user->id)
            ->whereHas(
                'role',
                fn($q) =>
                $q->where('name', 'Owner')
            )
            ->exists();
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
