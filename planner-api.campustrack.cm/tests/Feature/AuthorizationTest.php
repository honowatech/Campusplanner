<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    private Department $department;

    private Department $otherDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        // Permissions et rôles réels (tels que seedés en production)
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->department = Department::factory()->create();
        $this->otherDepartment = Department::factory()->create();
    }

    private function userWithRole(string $role, ?Department $department = null): User
    {
        $user = User::factory()->create([
            'department_id' => ($department ?? $this->department)->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    /** @test */
    public function unauthenticated_user_cannot_access_resources(): void
    {
        $this->getJson('/api/teachers')->assertStatus(401);
        $this->getJson('/api/rooms')->assertStatus(401);
    }

    /** @test */
    public function etudiant_cannot_list_or_delete_teachers(): void
    {
        $etudiant = $this->userWithRole('etudiant');
        $teacher = Teacher::factory()->create(['department_id' => $this->department->id]);

        $this->actingAs($etudiant);
        $this->getJson('/api/teachers')->assertStatus(403);
        $this->deleteJson("/api/teachers/{$teacher->id}")->assertStatus(403);
    }

    /** @test */
    public function professeur_cannot_create_rooms(): void
    {
        $professeur = $this->userWithRole('professeur');

        $this->actingAs($professeur);
        $this->postJson('/api/rooms', [
            'name' => 'Salle test',
            'code' => 'A101',
            'capacity' => 30,
        ])->assertStatus(403);
    }

    /** @test */
    public function professeur_cannot_view_rooms(): void
    {
        $professeur = $this->userWithRole('professeur');

        $this->actingAs($professeur);
        $this->getJson('/api/rooms')->assertStatus(403);
    }

    /** @test */
    public function personnel_administratif_can_view_rooms_but_not_create(): void
    {
        $personnel = $this->userWithRole('personnel-administratif');

        $this->actingAs($personnel);
        $this->getJson('/api/rooms')->assertStatus(200);
        $this->postJson('/api/rooms', [
            'name' => 'Salle test',
            'code' => 'A102',
            'capacity' => 30,
        ])->assertStatus(403);
    }

    /** @test */
    public function responsable_can_update_teacher_in_own_department_only(): void
    {
        $responsable = $this->userWithRole('responsable-departement');
        $ownTeacher = Teacher::factory()->create(['department_id' => $this->department->id]);
        $otherTeacher = Teacher::factory()->create(['department_id' => $this->otherDepartment->id]);

        $this->actingAs($responsable);
        $this->putJson("/api/teachers/{$ownTeacher->id}", ['phone' => '600000000'])
            ->assertStatus(200);
        $this->putJson("/api/teachers/{$otherTeacher->id}", ['phone' => '600000001'])
            ->assertStatus(403);
    }

    /** @test */
    public function responsable_cannot_delete_departments(): void
    {
        $responsable = $this->userWithRole('responsable-departement');

        $this->actingAs($responsable);
        $this->deleteJson("/api/departments/{$this->otherDepartment->id}")->assertStatus(403);
    }

    /** @test */
    public function administrateur_can_manage_rooms(): void
    {
        $admin = $this->userWithRole('administrateur');

        $this->actingAs($admin);
        $response = $this->postJson('/api/rooms', [
            'name' => 'Amphi central',
            'code' => 'AMP1',
            'capacity' => 200,
            'type' => 'amphitheater',
        ]);
        $response->assertStatus(201);

        $room = Room::where('code', 'AMP1')->first();
        $this->deleteJson("/api/rooms/{$room->id}")->assertStatus(200);
    }

    /** @test */
    public function super_admin_bypasses_all_checks(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $room = Room::factory()->create();

        $this->actingAs($superAdmin);
        $this->getJson('/api/rooms')->assertStatus(200);
        $this->deleteJson("/api/rooms/{$room->id}")->assertStatus(200);
    }

    /** @test */
    public function etudiant_cannot_generate_schedules(): void
    {
        $etudiant = $this->userWithRole('etudiant');

        $this->actingAs($etudiant);
        $this->postJson('/api/plannings/generate', [
            'planning_id' => 1,
            'classes' => [],
            'courses' => [],
        ])->assertStatus(403);
    }

    /** @test */
    public function etudiant_cannot_create_plannings(): void
    {
        $etudiant = $this->userWithRole('etudiant');

        $this->actingAs($etudiant);
        $startingDate = now()->startOfWeek()->addWeek()->toDateString();

        $this->postJson('/api/plannings', [
            'type' => 'weekly',
            'starting_date' => $startingDate,
            'ending_date' => Carbon::parse($startingDate)->addDays(6)->toDateString(),
        ])->assertStatus(403);
    }
}
