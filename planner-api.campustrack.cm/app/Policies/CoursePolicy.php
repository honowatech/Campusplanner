<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('courses.view');
    }

    public function view(User $user, Course $course): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('courses.manage');
    }

    public function update(User $user, Course $course): bool
    {
        if (! $user->hasPermissionTo('courses.manage')) {
            return false;
        }

        return $this->hasGlobalScope($user, 'teachers.view.all')
            || $this->sameDepartment($user, $course->department_id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
}
