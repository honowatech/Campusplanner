<?php

namespace App\Policies;

use App\Models\TeacherBlocking;
use App\Models\User;

class TeacherBlockingPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'teachers.view.all',
            'teachers.view.department',
            'teachers.block',
        ]);
    }

    public function view(User $user, TeacherBlocking $teacherBlocking): bool
    {
        if ($this->hasGlobalScope($user, 'teachers.view.all')) {
            return true;
        }

        return $user->hasAnyPermission(['teachers.view.department', 'teachers.block'])
            && $this->sameDepartment($user, $teacherBlocking->teacher?->department_id);
    }

    public function my(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('teachers.block');
    }

    public function update(User $user, TeacherBlocking $teacherBlocking): bool
    {
        if ($this->hasGlobalScope($user, 'teachers.view.all')) {
            return true;
        }

        return $user->hasPermissionTo('teachers.block')
            && $this->sameDepartment($user, $teacherBlocking->teacher?->department_id);
    }

    public function delete(User $user, TeacherBlocking $teacherBlocking): bool
    {
        return $this->update($user, $teacherBlocking);
    }

    public function approve(User $user, TeacherBlocking $teacherBlocking): bool
    {
        return $this->update($user, $teacherBlocking);
    }

    public function reject(User $user, TeacherBlocking $teacherBlocking): bool
    {
        return $this->update($user, $teacherBlocking);
    }
}
