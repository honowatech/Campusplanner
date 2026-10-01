<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;
use Tests\TestCase;

class PlanningScopeTest extends TestCase
{
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();
    }

    /** @test */
    public function professeur_ne_peut_editer_que_les_shift_plannings_de_ses_classes(): void
    {
        $professeur = User::factory()->create(['department_id' => $this->department->id]);
        $professeur->assignRole('professeur');

        $teacher = Teacher::factory()->create([
            'user_id' => $professeur->id,
            'department_id' => $this->department->id,
        ]);

        $planning = Planning::create([
            'starting_date' => now()->startOfWeek()->addWeek()->toDateString(),
            'ending_date' => now()->startOfWeek()->addWeek()->addDays(6)->toDateString(),
            'description' => 'Scope test',
        ]);

        $ownClass = CourseClass::create([
            'name' => 'Classe A', 'code' => 'CA', 'department_id' => $this->department->id,
            'level' => 1, 'capacity' => 30, 'academic_year' => '2026-2027',
        ]);

        $otherClass = CourseClass::create([
            'name' => 'Classe B', 'code' => 'CB', 'department_id' => $this->department->id,
            'level' => 1, 'capacity' => 30, 'academic_year' => '2026-2027',
        ]);

        $course = Course::create([
            'name' => 'Cours X', 'code' => 'CX', 'department_id' => $this->department->id,
        ]);

        $date = now()->startOfWeek()->addWeek()->toDateString();

        // Le professeur enseigne dans ownClass
        $ownShift = ShiftPlanning::create([
            'planning_id' => $planning->id,
            'course_class_id' => $ownClass->id,
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'date' => $date,
            'starting_hour' => '08:00',
            'ending_hour' => '10:00',
        ]);

        // Autre shift, dans otherClass, enseigné par quelqu'un d'autre
        $otherShift = ShiftPlanning::create([
            'planning_id' => $planning->id,
            'course_class_id' => $otherClass->id,
            'course_id' => $course->id,
            'teacher_id' => null,
            'date' => $date,
            'starting_hour' => '10:00',
            'ending_hour' => '12:00',
        ]);

        $this->actingAs($professeur);

        $this->assertTrue($professeur->can('view', $ownShift));
        $this->assertTrue($professeur->can('update', $ownShift));
        $this->assertFalse($professeur->can('view', $otherShift));
        $this->assertFalse($professeur->can('update', $otherShift));
    }
}
