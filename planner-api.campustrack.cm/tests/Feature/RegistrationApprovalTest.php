<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class RegistrationApprovalTest extends TestCase
{
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->department = Department::factory()->create();

        // Deux tests passent par /api/login : l'endpoint n'ouvre de session que
        // si la requête est stateful (Referer du front).
        $this->usePersistedSession();
        $this->asSpaClient();
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'requested_role' => 'professeur',
            'department_id' => $this->department->id,
        ], $overrides);
    }

    /** @test */
    public function registration_creates_a_pending_account_without_token(): void
    {
        $response = $this->postJson('/api/register', $this->registerPayload());

        $response->assertStatus(201)
            ->assertJsonMissingPath('data.token');

        $user = User::where('email', 'jean.dupont@example.com')->first();
        $this->assertFalse($user->is_approved);
        $this->assertEquals('professeur', $user->requested_role);
        $this->assertEmpty($user->tokens);
    }

    /** @test */
    public function registration_rejects_admin_roles(): void
    {
        $this->postJson('/api/register', $this->registerPayload([
            'requested_role' => 'administrateur',
        ]))->assertStatus(422);

        $this->postJson('/api/register', $this->registerPayload([
            'requested_role' => 'super-admin',
        ]))->assertStatus(422);
    }

    /** @test */
    public function pending_user_cannot_login(): void
    {
        $this->postJson('/api/register', $this->registerPayload());

        $response = $this->postJson('/api/login', [
            'email' => 'jean.dupont@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_approve_accounts(): void
    {
        $this->postJson('/api/register', $this->registerPayload());
        $pendingId = User::where('email', 'jean.dupont@example.com')->first()->id;

        $professeur = User::factory()->create(['department_id' => $this->department->id]);
        $professeur->assignRole('professeur');

        $this->actingAs($professeur);
        $this->postJson("/api/users/{$pendingId}/approve")->assertStatus(403);
    }

    /** @test */
    public function admin_can_approve_and_user_can_then_login(): void
    {
        $this->postJson('/api/register', $this->registerPayload());
        $pendingId = User::where('email', 'jean.dupont@example.com')->first()->id;

        $admin = User::factory()->create(['department_id' => $this->department->id]);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $approveResponse = $this->postJson("/api/users/{$pendingId}/approve");
        $approveResponse->assertStatus(200);

        $user = User::find($pendingId);
        $this->assertTrue($user->is_approved);
        $this->assertEquals($admin->id, $user->approved_by);
        $this->assertNotNull($user->approved_at);
        $this->assertTrue($user->hasRole('professeur'));

        // Le compte validé peut maintenant se connecter : la réponse ne
        // contient aucun token, l'accès passe par le cookie de session.
        $this->forgetResolvedGuards();
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'jean.dupont@example.com',
            'password' => 'Password123!',
        ]);
        $loginResponse->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.token');
        $this->assertAuthenticatedAs($user, 'web');
    }

    /** @test */
    public function admin_can_override_requested_role_on_approval(): void
    {
        $this->postJson('/api/register', $this->registerPayload([
            'requested_role' => 'etudiant',
        ]));
        $pendingId = User::where('email', 'jean.dupont@example.com')->first()->id;

        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->postJson("/api/users/{$pendingId}/approve", ['role' => 'personnel-administratif'])
            ->assertStatus(200);

        $this->assertTrue(User::find($pendingId)->hasRole('personnel-administratif'));
    }

    /** @test */
    public function admin_can_reject_a_pending_account(): void
    {
        $this->postJson('/api/register', $this->registerPayload());
        $pendingId = User::where('email', 'jean.dupont@example.com')->first()->id;

        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->postJson("/api/users/{$pendingId}/reject")->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $pendingId]);
    }

    /** @test */
    public function approved_users_are_not_listed_as_pending(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->postJson('/api/register', $this->registerPayload());
        $this->postJson('/api/register', $this->registerPayload([
            'email' => 'autre@example.com',
            'requested_role' => 'etudiant',
        ]));

        $response = $this->getJson('/api/users/pending');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.users.data'));

        // Le compte admin existant (déjà validé par défaut) n'apparaît pas
        $this->assertNotContains($admin->id, array_column($response->json('data.users.data'), 'id'));
    }
}
