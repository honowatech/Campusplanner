<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['teachers.view.all', 'teachers.view.department']);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        if ($this->hasGlobalScope($user, 'teachers.view.all')) {
            return true;
        }

        return $user->hasPermissionTo('teachers.view.department')
            && $this->sameDepartment($user, $teacher->department_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['teachers.view.all', 'teachers.manage.department']);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        if ($this->hasGlobalScope($user, 'teachers.view.all')) {
            return true;
        }

        return $user->hasPermissionTo('teachers.manage.department')
            && $this->sameDepartment($user, $teacher->department_id);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->update($user, $teacher);
    }
}
