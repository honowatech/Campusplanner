<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesDataScope
{
    /**
     * Restreint la liste des étudiants au périmètre de l'utilisateur
     * (global > département > classe), en miroir de StudentPolicy::view.
     */
    protected function scopeStudents(Builder $query, User $user): Builder
    {
        if ($user->hasPermissionTo('students.view.all')) {
            return $query;
        }

        if ($user->hasPermissionTo('students.view.department')) {
            if ($user->department_id === null) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereHas('class', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        if ($user->hasPermissionTo('students.view.class')) {
            return $query->whereIn('course_class_id', $user->classIds());
        }

        return $query;
    }

    /**
     * Restreint la liste des enseignants au périmètre de l'utilisateur
     * (global > département), en miroir de TeacherPolicy::view.
     */
    protected function scopeTeachers(Builder $query, User $user): Builder
    {
        if ($user->hasPermissionTo('teachers.view.all')) {
            return $query;
        }

        if ($user->hasPermissionTo('teachers.view.department')) {
            if ($user->department_id === null) {
                return $query->whereRaw('0 = 1');
            }

            return $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    /**
     * Restreint la liste des classes au département de l'utilisateur pour les
     * rôles à périmètre département. Le scope global (super-admin ou
     * administrateur via teachers.view.all) n'est pas filtré.
     */
    protected function scopeClasses(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super-admin') || $user->hasPermissionTo('teachers.view.all')) {
            return $query;
        }

        if ($user->department_id !== null) {
            $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    /**
     * Restreint la liste des matières au périmètre de l'utilisateur : un
     * responsable ne voit/gère que les matières de son département. Le scope
     * global (super-admin / administrateur) et la consultation simple
     * (catalogue) ne sont pas filtrés.
     */
    protected function scopeCourses(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super-admin') || $user->hasPermissionTo('teachers.view.all')) {
            return $query;
        }

        if ($user->hasPermissionTo('courses.manage')) {
            if ($user->department_id === null) {
                return $query->whereRaw('0 = 1');
            }

            return $query->where('department_id', $user->department_id);
        }

        return $query;
    }

    /**
     * Département restreint pour les rôles à périmètre département, ou null
     * pour un périmètre global (super-admin / administrateur).
     */
    protected function departmentScope(User $user): ?int
    {
        if ($user->hasRole('super-admin') || $user->hasPermissionTo('teachers.view.all')) {
            return null;
        }

        return $user->department_id;
    }
}
