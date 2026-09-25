<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['students.view.all', 'students.view.department', 'students.view.class']);
    }

    public function view(User $user, Student $student): bool
    {
        if ($this->hasGlobalScope($user, 'students.view.all')) {
            return true;
        }

        if ($user->hasPermissionTo('students.view.department')) {
            return $this->sameDepartment($user, $student->class?->department_id);
        }

        return $user->hasPermissionTo('students.view.class');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['students.view.all', 'students.create']);
    }

    public function update(User $user, Student $student): bool
    {
        if ($this->hasGlobalScope($user, 'students.view.all')) {
            return true;
        }

        return $user->hasPermissionTo('students.edit')
            && $this->sameDepartment($user, $student->class?->department_id);
    }

    public function delete(User $user, Student $student): bool
    {
        if ($this->hasGlobalScope($user, 'students.view.all')) {
            return true;
        }

        return $user->hasPermissionTo('students.delete')
            && $this->sameDepartment($user, $student->class?->department_id);
    }
}
