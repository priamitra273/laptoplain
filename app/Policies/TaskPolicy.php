<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;

class TaskPolicy
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
    public function view(User $user, Task $task): bool
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
        
        $isMember = $task->project->projectMembers
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
    public function create(User $user, Project $project): bool
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

        $isOwner = $project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', $user->id)
            ->where('role.name', 'Owner')
            ->isNotEmpty();

        if ($isOwner) {
            return $isOwner;
        }

        $isMember = $project->projectMembers()
            ->where('user_id', $user->id)
            ->exists();

        return $isMember;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
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

        $isOwner = $task->project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', $user->id)
            ->where('role.name', 'Owner')
            ->isNotEmpty();

        if ($isOwner) {
            return $isOwner;
        }

        $isMember = $task->users()
            ->where('user_id', $user->id)
            ->exists();

        return $isMember;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
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

        $isOwner = $task->project->projectMembers
            ->filter(function ($member) {
                return $member->user !== null;
            })
            ->where('user_id', $user->id)
            ->where('role.name', 'Owner')
            ->isNotEmpty();

        if ($isOwner) {
            return $isOwner;
        }

        $isMember = $task->users()
            ->where('user_id', $user->id)
            ->exists();

        return $isMember;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
