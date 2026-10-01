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
        if ($user->hasPermissionTo('plannings.view.all')) {
            return true;
        }

        if ($user->hasPermissionTo('plannings.view.department')) {
            return $this->sameDepartment($user, $planning->department_id);
        }

        if ($user->hasPermissionTo('plannings.view.class')) {
            return $planning->shiftPlannings()
                ->whereIn('course_class_id', $this->userClassIds($user))
                ->exists();
        }

        if ($user->hasPermissionTo('plannings.view.subject')) {
            return $planning->shiftPlannings()
                ->whereIn('course_id', $this->userCourseIds($user))
                ->exists();
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

    public function update(User $user, Planning $planning): bool
    {
        if ($user->hasPermissionTo('plannings.edit.all')) {
            return true;
        }

        if ($user->hasPermissionTo('plannings.edit.department')) {
            return $this->sameDepartment($user, $planning->department_id);
        }

        if ($user->hasPermissionTo('plannings.edit.class')) {
            return $planning->shiftPlannings()
                ->whereIn('course_class_id', $this->userClassIds($user))
                ->exists();
        }

        if ($user->hasPermissionTo('plannings.edit.subject')) {
            return $planning->shiftPlannings()
                ->whereIn('course_id', $this->userCourseIds($user))
                ->exists();
        }

        return false;
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
