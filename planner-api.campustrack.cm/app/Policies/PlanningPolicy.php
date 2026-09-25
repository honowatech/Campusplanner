<?php

namespace App\Policies;

use App\Models\User;
use Modules\Planning\Entities\Planning;

class PlanningPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'plannings.view.all',
            'plannings.view.department',
            'plannings.view.class',
            'plannings.view.subject',
        ]);
    }

    public function view(User $user, Planning $planning): bool
    {
        if ($this->hasGlobalScope($user, 'plannings.view.all')) {
            return true;
        }

        return $user->hasAnyPermission(['plannings.view.department', 'plannings.view.class', 'plannings.view.subject'])
            && $this->sameDepartment($user, $planning->department_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'plannings.create.all',
            'plannings.create.department',
            'plannings.create.class',
            'plannings.create.subject',
        ]);
    }

    public function update(User $user, Planning $planning): bool
    {
        if ($user->hasAnyPermission(['plannings.edit.all', 'plannings.edit.class', 'plannings.edit.subject'])) {
            return true;
        }

        return $user->hasPermissionTo('plannings.edit.department')
            && $this->sameDepartment($user, $planning->department_id);
    }

    public function delete(User $user, Planning $planning): bool
    {
        if ($user->hasPermissionTo('plannings.delete.all')) {
            return true;
        }

        return $user->hasPermissionTo('plannings.delete.department')
            && $this->sameDepartment($user, $planning->department_id);
    }

    public function generate(User $user): bool
    {
        return $user->hasPermissionTo('plannings.generate.auto');
    }

    public function detectConflicts(User $user): bool
    {
        return $user->hasAnyPermission(['plannings.detect.conflicts', 'plannings.generate.auto']);
    }

    public function resolveConflicts(User $user): bool
    {
        return $user->hasPermissionTo('plannings.generate.auto');
    }

    public function optimize(User $user): bool
    {
        return $user->hasPermissionTo('plannings.generate.auto');
    }
}
