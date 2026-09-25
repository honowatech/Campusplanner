<?php

namespace App\Policies;

use App\Models\User;

abstract class Policy
{
    /**
     * Le super-admin contourne toutes les vérifications de policy.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * L'utilisateur a-t-il un périmètre global sur la ressource
     * (par opposition à un périmètre limité à son département) ?
     */
    protected function hasGlobalScope(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    protected function sameDepartment(User $user, ?int $departmentId): bool
    {
        return $user->department_id !== null
            && (int) $user->department_id === (int) $departmentId;
    }
}
