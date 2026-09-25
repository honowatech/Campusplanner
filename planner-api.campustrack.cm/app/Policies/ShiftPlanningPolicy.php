<?php

namespace App\Policies;

use App\Models\User;
use Modules\Planning\Entities\ShiftPlanning;

class ShiftPlanningPolicy extends Policy
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

    public function view(User $user, ShiftPlanning $shiftPlanning): bool
    {
        if ($this->hasGlobalScope($user, 'plannings.view.all')) {
            return true;
        }

        return $user->hasAnyPermission(['plannings.view.department', 'plannings.view.class', 'plannings.view.subject'])
            && $this->sameDepartment($user, $shiftPlanning->planning?->department_id);
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

    public function update(User $user, ShiftPlanning $shiftPlanning): bool
    {
        if ($user->hasAnyPermission(['plannings.edit.all', 'plannings.edit.class', 'plannings.edit.subject'])) {
            return true;
        }

        return $user->hasPermissionTo('plannings.edit.department')
            && $this->sameDepartment($user, $shiftPlanning->planning?->department_id);
    }

    public function delete(User $user, ShiftPlanning $shiftPlanning): bool
    {
        if ($user->hasPermissionTo('plannings.delete.all')) {
            return true;
        }

        return $user->hasPermissionTo('plannings.delete.department')
            && $this->sameDepartment($user, $shiftPlanning->planning?->department_id);
    }
}
