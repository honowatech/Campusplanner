<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Department;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;
use Tests\TestCase;

class PlanningShowRelationsTest extends TestCase
{
    private User $admin;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();
        $this->admin = User::factory()->create(['department_id' => $this->department->id]);
        $this->admin->assignRole('administrateur');
    }

    /** @test */
    public function planning_show_returns_shift_plannings_with_embedded_relations(): void
    {
        $class = CourseClass::create([
            'name' => 'Licence 1',
            'code' => 'L1',
            'department_id' => $this->department->id,
            'level' => 1,
            'capacity' => 30,
            'academic_year' => '2026-2027',
        ]);

        $course = Course::create([
            'name' => 'Algorithmique',
            'code' => 'ALGO',
            'department_id' => $this->department->id,
        ]);

        $teacher = Teacher::factory()->create(['department_id' => $this->department->id]);

        $room = Room::factory()->create(['department_id' => $this->department->id]);

        $planning = Planning::create([
            'type' => 'weekly',
            'starting_date' => now()->startOfWeek()->addWeek()->toDateString(),
            'ending_date' => now()->startOfWeek()->addWeek()->addDays(6)->toDateString(),
            'description' => 'Semaine test',
            'department_id' => $this->department->id,
        ]);

        ShiftPlanning::create([
            'planning_id' => $planning->id,
            'course_class_id' => $class->id,
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'date' => now()->addWeek()->toDateString(),
            'starting_hour' => '08:00',
            'ending_hour' => '10:00',
        ]);

        $this->actingAs($this->admin)
            ->getJson("/api/plannings/{$planning->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.planning.shift_plannings')
            ->assertJsonPath('data.planning.shift_plannings.0.course_class.id', $class->id)
            ->assertJsonPath('data.planning.shift_plannings.0.course_class.code', 'L1')
            ->assertJsonPath('data.planning.shift_plannings.0.course.id', $course->id)
            ->assertJsonPath('data.planning.shift_plannings.0.course.code', 'ALGO')
            ->assertJsonPath('data.planning.shift_plannings.0.teacher.id', $teacher->id)
            ->assertJsonPath('data.planning.shift_plannings.0.teacher.full_name', $teacher->full_name)
            ->assertJsonPath('data.planning.shift_plannings.0.room.id', $room->id)
            ->assertJsonPath('data.planning.shift_plannings.0.room.name', $room->name);
    }
}
