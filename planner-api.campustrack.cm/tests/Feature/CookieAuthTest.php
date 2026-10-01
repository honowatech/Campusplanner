<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

/**
 * Contrat d'authentification du front SPA : session cookie uniquement.
 *
 * Le scenario `full_spa_flow` rejoue le parcours complet dans un seul test ;
 * les autres tests utilisent withBrowserSession(), qui injecte une session
 * déjà persistée + son cookie, afin que l'authentification soit prouvée par
 * le cookie seul (le Store de session étant un singleton du conteneur, un
 * test multi-appels pourrait sinon réutiliser un état en mémoire).
 */
class CookieAuthTest extends TestCase
{
    private const PASSWORD = 'Password123!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usePersistedSession();
        $this->asSpaClient();
    }

    /** @test */
    public function csrf_cookie_endpoint_sets_the_xsrf_cookie(): void
    {
        $response = $this->get('/api/csrf-cookie');

        $response->assertStatus(204);

        $hasXsrf = collect($response->headers->getCookies())
            ->contains(fn ($cookie) => $cookie->getName() === 'XSRF-TOKEN');
        $this->assertTrue($hasXsrf, 'Le cookie XSRF-TOKEN doit être posé.');
    }

    /** @test */
    public function login_returns_a_session_cookie_and_no_token(): void
    {
        $user = $this->approvedUser('login@example.com');

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles.0.name', 'administrateur')
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('token');

        $this->assertNotEmpty($response->json('data.permissions'), 'Les permissions effectives doivent être renvoyées.');

        $this->captureSessionCookie($response);
    }

    /** @test */
    public function an_existing_session_cookie_alone_authenticates_the_request(): void
    {
        $user = $this->approvedUser('cookie-only@example.com');

        $this->withBrowserSession($user);

        $this->getJson('/api/auth-user')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    /** @test */
    public function the_session_cookie_is_only_honoured_for_a_stateful_referer(): void
    {
        $user = $this->approvedUser('stateful@example.com');
        $this->withBrowserSession($user);

        // Referer hors SANCTUM_STATEFUL_DOMAINS : le cookie est ignoré.
        $this->withHeader('Referer', 'https://example.com/');
        $this->getJson('/api/auth-user')->assertStatus(401);

        // Referer du front : la session est relue depuis le cookie.
        $this->forgetResolvedGuards();
        $this->withHeader('Referer', 'http://localhost:3000/dashboard');
        $this->getJson('/api/auth-user')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);
    }

    /** @test */
    public function full_spa_flow_login_then_authenticated_requests_then_logout(): void
    {
        $user = $this->approvedUser('spa@example.com');
        Department::factory()->count(2)->create();
        $expectedDepartments = Department::count();

        // 1. Login : session posée, aucun token renvoyé au client.
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);
        $login->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.token');
        $this->captureSessionCookie($login);

        // 2. Requêtes authentifiées par le cookie de session.
        $this->forgetResolvedGuards();
        $this->getJson('/api/auth-user')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);

        // 3. Une ressource protégée par permission répond aussi par session.
        $this->forgetResolvedGuards();
        $this->getJson('/api/departments')
            ->assertStatus(200)
            ->assertJsonCount($expectedDepartments, 'data.departments.data');

        // 4. Logout : la session est invalidée et un nouveau cookie est posé.
        $this->forgetResolvedGuards();
        $logout = $this->postJson('/api/logout');
        $logout->assertStatus(200)->assertJsonMissingPath('data.token');
        $this->captureSessionCookie($logout);

        // 5. Le nouveau cookie ne donne plus accès à l'API.
        $this->forgetResolvedGuards();
        $this->getJson('/api/auth-user')->assertStatus(401);

        $this->forgetResolvedGuards();
        $this->getJson('/api/departments')->assertStatus(401);
    }

    /** @test */
    public function authenticated_endpoints_reject_requests_without_the_session_cookie(): void
    {
        $this->approvedUser('nocookie@example.com');

        $this->getJson('/api/auth-user')->assertStatus(401);
        $this->getJson('/api/departments')->assertStatus(401);
    }

    /** @test */
    public function bad_credentials_are_rejected_without_opening_a_session(): void
    {
        $this->approvedUser('inconnu@example.com');

        $response = $this->postJson('/api/login', [
            'email' => 'inconnu@example.com',
            'password' => 'mauvais-mot-de-passe',
        ]);

        $response->assertStatus(401)->assertJsonMissingPath('data.user');
        $this->assertGuest('web');
    }

    /** @test */
    public function a_pending_account_cannot_open_a_session(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => self::PASSWORD,
            'is_approved' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Votre compte est en attente de validation par un administrateur.');

        $this->assertGuest('web');
    }

    private function approvedUser(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => self::PASSWORD,
            'is_approved' => true,
        ]);

        $user->assignRole('administrateur');

        return $user;
    }
}
