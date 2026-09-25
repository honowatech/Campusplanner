<?php

namespace App\Policies;

use App\Models\CourseClass;
use App\Models\User;

class CourseClassPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('classes.view');
    }

    public function view(User $user, CourseClass $courseClass): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('classes.manage')
            || $this->hasGlobalScope($user, 'teachers.view.all');
    }

    public function update(User $user, CourseClass $courseClass): bool
    {
        if (! $user->hasAnyPermission(['classes.manage', 'teachers.view.all'])) {
            return false;
        }

        return $this->hasGlobalScope($user, 'teachers.view.all')
            || $this->sameDepartment($user, $courseClass->department_id);
    }

    public function delete(User $user, CourseClass $courseClass): bool
    {
        return $this->update($user, $courseClass);
    }
}
