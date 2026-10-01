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
        if ($user->hasPermissionTo('plannings.view.all')) {
            return true;
        }

        if ($user->hasPermissionTo('plannings.view.department')) {
            return $this->sameDepartment($user, $shiftPlanning->planning?->department_id);
        }

        if ($user->hasPermissionTo('plannings.view.class')) {
            return in_array($shiftPlanning->course_class_id, $this->userClassIds($user));
        }

        if ($user->hasPermissionTo('plannings.view.subject')) {
            return in_array($shiftPlanning->course_id, $this->userCourseIds($user));
        }

        return false;
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
        if ($user->hasPermissionTo('plannings.edit.all')) {
            return true;
        }

        if ($user->hasPermissionTo('plannings.edit.department')) {
            return $this->sameDepartment($user, $shiftPlanning->planning?->department_id);
        }

        if ($user->hasPermissionTo('plannings.edit.class')) {
            return in_array($shiftPlanning->course_class_id, $this->userClassIds($user));
        }

        if ($user->hasPermissionTo('plannings.edit.subject')) {
            return in_array($shiftPlanning->course_id, $this->userCourseIds($user));
        }

        return false;
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
