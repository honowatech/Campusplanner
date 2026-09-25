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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AtomicityAndDurationTest extends TestCase
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
    public function student_creation_is_atomic_when_role_assignment_fails(): void
    {
        // Le rôle etudiant disparaît : assignRole échouera APRÈS User::create.
        // Sans transaction, un utilisateur orphelin (sans fiche étudiant) resterait.
        Role::where('name', 'etudiant')->delete();

        $this->assertTrue($this->admin->hasPermissionTo('students.view.all'));

        $response = $this->actingAs($this->admin)->postJson('/api/students', [
            'first_name' => 'Atomique',
            'last_name' => 'Test',
            'email' => 'atomique.test@example.com',
            'matricule' => 'ATM-001',
            'admission_date' => now()->toDateString(),
            'password' => 'Password123!',
        ]);

        $this->assertNotSame(201, $response->status(), 'La création aurait dû échouer (rôle manquant)');

        $this->assertDatabaseMissing('users', ['email' => 'atomique.test@example.com']);
        $this->assertDatabaseMissing('students', ['matricule' => 'ATM-001']);
    }

    /** @test */
    public function teacher_creation_with_courses_is_atomic(): void
    {
        $course1 = Course::create([
            'name' => 'Algorithmique',
            'code' => 'ALGO-101',
            'department_id' => $this->department->id,
        ]);
        $course2 = Course::create([
            'name' => 'Bases de données',
            'code' => 'BD-201',
            'department_id' => $this->department->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/teachers', [
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'email' => 'marie.curie@example.com',
            'speciality' => 'Physique',
            'department_id' => $this->department->id,
            'course_ids' => [$course1->id, $course2->id],
        ]);

        $response->assertStatus(201);

        $teacher = Teacher::where('email', 'marie.curie@example.com')->first();
        $this->assertNotNull($teacher);
        $this->assertCount(2, $teacher->courses);
    }

    /** @test */
    public function shift_duration_is_computed_in_real_hours(): void
    {
        $planning = Planning::create([
            'starting_date' => now()->startOfWeek()->addWeek()->toDateString(),
            'ending_date' => now()->startOfWeek()->addWeek()->addDays(6)->toDateString(),
            'description' => 'Semaine de test',
        ]);

        $class = CourseClass::create([
            'name' => 'Licence 3 A',
            'code' => 'L3A',
            'department_id' => $this->department->id,
            'capacity' => 30,
            'level' => 3,
            'academic_year' => '2026-2027',
        ]);

        $course = Course::create([
            'name' => 'Physique',
            'code' => 'PHY-101',
            'department_id' => $this->department->id,
        ]);

        // 08:00 -> 09:45 = 1,75 h réelle -> ceil = 2
        // (l'ancienne formule décimale donnait 1,45 h, masquée par ceil)
        $response = $this->actingAs($this->admin)->postJson('/api/plannings/shift-plannings', [
            'planning_id' => $planning->id,
            'course_class_id' => $class->id,
            'course_id' => $course->id,
            'date' => now()->startOfWeek()->addWeek()->addDay()->toDateString(),
            'starting_hour' => '08:00',
            'ending_hour' => '09:45',
        ]);

        $response->assertStatus(201);

        // 1,75 h réelle -> 2 blocs d'une heure (08:00-09:00 et 09:00-10:00)
        $shifts = ShiftPlanning::where('planning_id', $planning->id)
            ->orderBy('starting_hour')
            ->get();
        $this->assertCount(2, $shifts);
        $this->assertStringContainsString('08:00', (string) $shifts[0]->starting_hour);
        $this->assertStringContainsString('09:00', (string) $shifts[0]->ending_hour);
        $this->assertStringContainsString('09:00', (string) $shifts[1]->starting_hour);
        $this->assertStringContainsString('10:00', (string) $shifts[1]->ending_hour);

        // 10:00 -> 10:30 = 0,5 h -> minimum 1 bloc, horaires conservés
        $response = $this->actingAs($this->admin)->postJson('/api/plannings/shift-plannings', [
            'planning_id' => $planning->id,
            'course_class_id' => $class->id,
            'course_id' => $course->id,
            'date' => now()->startOfWeek()->addWeek()->addDay()->toDateString(),
            'starting_hour' => '10:00',
            'ending_hour' => '10:30',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(3, ShiftPlanning::where('planning_id', $planning->id)->count());
    }
}
