<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

/**
 * Suspension / réactivation d'un compte en mode session cookie.
 *
 * Le middleware EnsureUserIsApproved coupe l'accès des sessions déjà ouvertes :
 * c'est le comportement qui remplaçait la révocation des tokens.
 */
class AccountSuspensionTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usePersistedSession();
        $this->asSpaClient();

        $department = Department::factory()->create();
        $this->admin = User::factory()->create(['department_id' => $department->id]);
        $this->admin->assignRole('administrateur');
    }

    /** @test */
    public function rejecting_a_pending_account_deletes_it_and_closes_any_open_session(): void
    {
        $user = User::factory()->create([
            'is_approved' => false,
            'requested_role' => 'professeur',
            'password' => 'Password123!',
        ]);
        $user->assignRole('professeur');

        // Session ouverte (le compte n'est pas validé : login impossible,
        // on simule donc une session ouverte avant le rejet).
        $this->withBrowserSession($user);
        $this->getJson('/api/auth-user')->assertStatus(403);

        $this->actingAs($this->admin);
        $this->postJson("/api/users/{$user->id}/reject")->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function deactivating_an_account_cuts_its_open_session_immediately(): void
    {
        $target = User::factory()->create(['is_approved' => true]);
        $target->assignRole('professeur');

        $this->withBrowserSession($target);
        $this->getJson('/api/auth-user')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $target->id);

        $this->actingAs($this->admin)
            ->postJson("/api/users/{$target->id}/deactivate")
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_approved' => false,
        ]);

        // La session ouverte est coupée immédiatement.
        $this->forgetResolvedGuards();
        $this->getJson('/api/auth-user')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Votre compte est en attente de validation ou a été suspendu par un administrateur.');
    }

    /** @test */
    public function deactivated_user_cannot_login(): void
    {
        $target = User::factory()->create([
            'is_approved' => true,
            'password' => 'Password123!',
        ]);
        $target->assignRole('professeur');

        $this->actingAs($this->admin)
            ->postJson("/api/users/{$target->id}/deactivate")
            ->assertStatus(200);

        $this->forgetResolvedGuards();
        $this->postJson('/api/login', [
            'email' => $target->email,
            'password' => 'Password123!',
        ])->assertStatus(403);
    }

    /** @test */
    public function deactivated_user_keeps_roles_and_can_be_reactivated(): void
    {
        $target = User::factory()->create(['is_approved' => true]);
        $target->assignRole('personnel-administratif');

        $this->actingAs($this->admin);
        $this->postJson("/api/users/{$target->id}/deactivate")->assertStatus(200);

        // Réactivation sans rôle fourni : les rôles existants sont conservés
        $response = $this->postJson("/api/users/{$target->id}/approve");
        $response->assertStatus(200);

        $target->refresh();
        $this->assertTrue($target->is_approved);
        $this->assertTrue($target->hasRole('personnel-administratif'));
    }

    /** @test */
    public function deactivated_accounts_do_not_appear_in_pending_registrations(): void
    {
        $target = User::factory()->create(['is_approved' => true]);
        $target->assignRole('professeur');

        $this->actingAs($this->admin);
        $this->postJson("/api/users/{$target->id}/deactivate")->assertStatus(200);

        // Une vraie demande d'inscription pour contraster
        $this->postJson('/api/register', [
            'name' => 'Nouvel Utilisateur',
            'email' => 'nouvel@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'requested_role' => 'etudiant',
        ]);

        $response = $this->getJson('/api/users/pending');
        $emails = array_column($response->json('data.users.data'), 'email');
        $this->assertContains('nouvel@example.com', $emails);
        $this->assertNotContains($target->email, $emails);
    }

    /** @test */
    public function deactivated_account_still_listed_in_users_index(): void
    {
        $target = User::factory()->create(['is_approved' => true]);
        $target->assignRole('professeur');

        $this->actingAs($this->admin);
        $this->postJson("/api/users/{$target->id}/deactivate")->assertStatus(200);

        $response = $this->getJson('/api/users');
        $emails = array_column($response->json('data.users.data'), 'email');
        $this->assertContains($target->email, $emails);
    }

    /** @test */
    public function admin_cannot_deactivate_himself(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/users/{$this->admin->id}/deactivate")
            ->assertStatus(400);
    }

    /** @test */
    public function non_admin_cannot_deactivate(): void
    {
        $target = User::factory()->create(['is_approved' => true]);
        $professeur = User::factory()->create();
        $professeur->assignRole('professeur');

        $this->actingAs($professeur)
            ->postJson("/api/users/{$target->id}/deactivate")
            ->assertStatus(403);
    }
}
