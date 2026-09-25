<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class UserCreationWithRolesTest extends TestCase
{
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->department = Department::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Compte Test',
            'email' => 'compte.test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'department_id' => $this->department->id,
            'roles' => ['professeur'],
        ], $overrides);
    }

    /** @test */
    public function admin_can_create_user_with_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin)
            ->postJson('/api/users', $this->payload())
            ->assertStatus(201);

        $user = User::where('email', 'compte.test@example.com')->first();
        $this->assertTrue($user->hasRole('professeur'));
        $this->assertTrue($user->is_approved); // créé par un admin : actif d'office
    }

    /** @test */
    public function admin_cannot_assign_admin_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin)
            ->postJson('/api/users', $this->payload(['roles' => ['administrateur']]))
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson('/api/users', $this->payload([
                'email' => 'autre@example.com',
                'roles' => ['super-admin'],
            ]))
            ->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_assign_admin_roles(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->postJson('/api/users', $this->payload(['roles' => ['administrateur']]))
            ->assertStatus(201);

        $this->assertTrue(
            User::where('email', 'compte.test@example.com')->first()->hasRole('administrateur')
        );
    }

    /** @test */
    public function unknown_role_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin)
            ->postJson('/api/users', $this->payload(['roles' => ['role-inexistant']]))
            ->assertStatus(422);
    }
}
