<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    private function resolveAccess(User $user, Task $task, array $fields): bool
    {
        $attributes = $task->getAttributes();

        $values = [];
        $missing = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $attributes)) {
                $values[$field] = (bool) $task->$field;

                // short circuit
                if ($values[$field] === true) {
                    return true;
                }
            } else {
                $missing[] = $field;
            }
        }

        // semua field tersedia tapi tidak ada yang true
        if (empty($missing)) {
            return false;
        }

        // fallback query hanya untuk field yang belum ada
        foreach ($missing as $field) {
            $value = $this->fallbackQuery($user, $task, $field);

            if ($value) {
                return true;
            }
        }

        return false;
    }

    private function fallbackQuery(User $user, Task $task, string $field): bool
    {
        return match ($field) {
            'is_task_member' => $task->users()
                ->where('user_id', $user->id)
                ->exists(),

            'is_project_member' => $task->project
                ->projectMembers()
                ->where('user_id', $user->id)
                ->exists(),

            'is_owner' => $task->project
                ->projectMembers()
                ->where('user_id', $user->id)
                ->whereHas('role', fn($q) => $q->where('name', 'Owner'))
                ->exists(),

            default => false,
        };
    }

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

        if ($user->can('task.read')) {
            return $this->resolveAccess($user, $task, [
                'is_project_member'
            ]);
        }

        return false;
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

        if ($user->can('task.create')) {
            // gunakan attribute jika controller memakai withExists
            if (array_key_exists('is_project_member', $project->getAttributes())) {
                return (bool) $project->is_project_member;
            }

            // fallback query jika attribute tidak ada
            $isMember = $project
                ->projectMembers()
                ->whereNotNull('user_id')
                ->where('user_id', $user->id)
                ->exists();

            // cache ke model supaya tidak query lagi jika policy dipanggil ulang
            $project->setAttribute('is_project_member', $isMember);

            return $isMember;
        }
        return false;
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

        if ($user->can('task.update')) {
            return $this->resolveAccess($user, $task, [
                'is_task_member',
                'is_owner'
            ]);
        }

        return false;
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

        if ($user->can('task.delete')) {
            return $this->resolveAccess($user, $task, [
                'is_task_member',
                'is_owner'
            ]);
        }
        return false;
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
