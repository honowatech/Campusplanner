<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Department;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;
use Tests\TestCase;

class CrudEndpointsTest extends TestCase
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

    // ---------------------------------------------------------------
    // Teachers
    // ---------------------------------------------------------------

    /** @test */
    public function teachers_crud_works_end_to_end(): void
    {
        // Index + Show accessibles
        $this->actingAs($this->admin)->getJson('/api/teachers')->assertStatus(200);

        $teacher = Teacher::factory()->create(['department_id' => $this->department->id]);
        // Régression : la relation renommée 'courses' ne doit plus provoquer de 500
        $this->actingAs($this->admin)
            ->getJson("/api/teachers/{$teacher->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.teacher.id', $teacher->id);

        // Store
        $response = $this->actingAs($this->admin)->postJson('/api/teachers', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada.lovelace@example.com',
            'speciality' => 'Informatique',
            'department_id' => $this->department->id,
        ]);
        $response->assertStatus(201);
        $newId = $response->json('data.teacher.id');

        // Update
        $this->actingAs($this->admin)
            ->putJson("/api/teachers/{$newId}", ['phone' => '600000000'])
            ->assertStatus(200)
            ->assertJsonPath('data.teacher.phone', '600000000');

        // Destroy
        $this->actingAs($this->admin)
            ->deleteJson("/api/teachers/{$newId}")
            ->assertStatus(200);
        $this->assertDatabaseMissing('teachers', ['id' => $newId]);
    }

    /** @test */
    public function teacher_deletion_is_refused_while_plannings_exist(): void
    {
        $teacher = Teacher::factory()->create(['department_id' => $this->department->id]);
        ShiftPlanning::create([
            'planning_id' => $this->makePlanning()->id,
            'course_class_id' => $this->makeClass(),
            'course_id' => $this->makeCourse(),
            'teacher_id' => $teacher->id,
            'date' => now()->addWeek()->toDateString(),
            'starting_hour' => '08:00',
            'ending_hour' => '10:00',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/teachers/{$teacher->id}")
            ->assertStatus(400);
    }

    // ---------------------------------------------------------------
    // Rooms
    // ---------------------------------------------------------------

    /** @test */
    public function rooms_crud_works_end_to_end(): void
    {
        $this->actingAs($this->admin)->getJson('/api/rooms')->assertStatus(200);

        $response = $this->actingAs($this->admin)->postJson('/api/rooms', [
            'name' => 'Amphi A',
            'code' => 'AMPA',
            'capacity' => 120,
            'type' => 'amphitheater',
        ]);
        $response->assertStatus(201);
        $roomId = $response->json('data.room.id');

        $this->actingAs($this->admin)
            ->getJson("/api/rooms/{$roomId}")
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->putJson("/api/rooms/{$roomId}", ['capacity' => 150])
            ->assertStatus(200)
            ->assertJsonPath('data.room.capacity', 150);

        $this->actingAs($this->admin)
            ->deleteJson("/api/rooms/{$roomId}")
            ->assertStatus(200);
        $this->assertDatabaseMissing('rooms', ['id' => $roomId]);
    }

    /** @test */
    public function room_validation_rejects_missing_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/rooms', ['name' => 'Salle incomplète'])
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Students
    // ---------------------------------------------------------------

    /** @test */
    public function students_crud_works_end_to_end(): void
    {
        $this->actingAs($this->admin)->getJson('/api/students')->assertStatus(200);

        $response = $this->actingAs($this->admin)->postJson('/api/students', [
            'first_name' => 'Nicolas',
            'last_name' => 'Dupont',
            'email' => 'nicolas.dupont@example.com',
            'matricule' => 'ETU-1001',
            'admission_date' => now()->toDateString(),
            'password' => 'Password123!',
        ]);
        $response->assertStatus(201);
        $studentId = $response->json('data.student.id');

        // Le compte utilisateur associé est créé avec le rôle etudiant
        $student = Student::find($studentId);
        $this->assertNotNull($student->user);
        $this->assertTrue($student->user->hasRole('etudiant'));

        $this->actingAs($this->admin)
            ->getJson("/api/students/{$studentId}")
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->putJson("/api/students/{$studentId}", ['phone' => '699999999'])
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->deleteJson("/api/students/{$studentId}")
            ->assertStatus(200);
        $this->assertDatabaseMissing('students', ['id' => $studentId]);
        $this->assertDatabaseMissing('users', ['email' => 'nicolas.dupont@example.com']);
    }

    // ---------------------------------------------------------------
    // Planning (module)
    // ---------------------------------------------------------------

    /** @test */
    public function plannings_crud_works_end_to_end(): void
    {
        $this->actingAs($this->admin)->getJson('/api/plannings')->assertStatus(200);

        $startingDate = now()->startOfWeek()->addWeek()->toDateString();

        $response = $this->actingAs($this->admin)->postJson('/api/plannings', [
            'type' => 'weekly',
            'starting_date' => $startingDate,
            'ending_date' => Carbon::parse($startingDate)->addDays(6)->toDateString(),
            'description' => 'Semaine 41',
        ]);
        $response->assertStatus(201);
        $planningId = $response->json('data.planning.id');

        $this->actingAs($this->admin)
            ->getJson("/api/plannings/{$planningId}")
            ->assertStatus(200)
            ->assertJsonPath('data.planning.id', $planningId);

        $this->actingAs($this->admin)
            ->putJson("/api/plannings/{$planningId}", [
                'type' => 'weekly',
                'starting_date' => $startingDate,
                'ending_date' => Carbon::parse($startingDate)->addDays(6)->toDateString(),
                'description' => 'Semaine 41 (révisée)',
            ])
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->deleteJson("/api/plannings/{$planningId}")
            ->assertStatus(200);
        $this->assertDatabaseMissing('planning_plannings', ['id' => $planningId]);
    }

    /** @test */
    public function etudiant_is_forbidden_on_all_crud(): void
    {
        $etudiant = User::factory()->create();
        $etudiant->assignRole('etudiant');

        $teacher = Teacher::factory()->create(['department_id' => $this->department->id]);
        $room = Room::factory()->create();

        $this->actingAs($etudiant)->getJson('/api/teachers')->assertStatus(403);
        $this->actingAs($etudiant)->postJson('/api/rooms', [])->assertStatus(403);
        $this->actingAs($etudiant)->deleteJson("/api/teachers/{$teacher->id}")->assertStatus(403);
        $this->actingAs($etudiant)->deleteJson("/api/rooms/{$room->id}")->assertStatus(403);
        $this->actingAs($etudiant)->getJson('/api/students')->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/teachers')->assertStatus(401);
        $this->getJson('/api/rooms')->assertStatus(401);
        $this->getJson('/api/students')->assertStatus(401);
        $this->getJson('/api/plannings')->assertStatus(401);
    }

    /** @test */
    public function dashboard_counts_real_schedule_conflicts(): void
    {
        $teacher = Teacher::factory()->create(['department_id' => $this->department->id]);
        $planningId = $this->makePlanning()->id;
        $classId = $this->makeClass();
        $courseId = $this->makeCourse();
        $date = now()->startOfWeek()->addWeek()->addDay()->toDateString();

        // Aucun chevauchement -> 0
        $response = $this->actingAs($this->admin)->getJson('/api/dashboard/overview?period=7d');
        $this->assertSame(0, (int) $response->json('data.realtime.schedule_conflicts'));

        // Le premier appel a mis le résultat en cache : purge avant de re-mesurer
        Cache::store(config('dashboard.cache.driver', 'redis'))->flush();

        // Deux cours du même enseignant qui se chevauchent -> 2 shifts en conflit
        ShiftPlanning::create([
            'planning_id' => $planningId, 'course_class_id' => $classId, 'course_id' => $courseId,
            'teacher_id' => $teacher->id, 'date' => $date,
            'starting_hour' => '08:00', 'ending_hour' => '10:00',
        ]);
        ShiftPlanning::create([
            'planning_id' => $planningId, 'course_class_id' => $classId, 'course_id' => $courseId,
            'teacher_id' => $teacher->id, 'date' => $date,
            'starting_hour' => '09:00', 'ending_hour' => '11:00',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/dashboard/overview?period=7d');
        $this->assertSame(2, (int) $response->json('data.realtime.schedule_conflicts'));
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makePlanning(): Planning
    {
        $startingDate = now()->startOfWeek()->addWeek()->toDateString();

        return Planning::create([
            'starting_date' => $startingDate,
            'ending_date' => Carbon::parse($startingDate)->addDays(6)->toDateString(),
            'description' => 'Planning helper',
        ]);
    }

    private function makeClass(): int
    {
        return CourseClass::create([
            'name' => 'Classe helper',
            'code' => 'HLP',
            'department_id' => $this->department->id,
            'level' => 1,
            'capacity' => 30,
            'academic_year' => '2026-2027',
        ])->id;
    }

    private function makeCourse(): int
    {
        return Course::create([
            'name' => 'Cours helper',
            'code' => 'CHLP',
            'department_id' => $this->department->id,
        ])->id;
    }
}
