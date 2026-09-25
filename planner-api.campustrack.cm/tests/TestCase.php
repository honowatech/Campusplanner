<?php

namespace Tests;

use App\Models\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load module migrations
        $this->loadModuleMigrations();

        // Create default roles
        $this->createDefaultRoles();
    }

    protected function loadModuleMigrations(): void
    {
        // Load Planning module migrations using the default database
        $this->artisan('migrate', [
            '--path' => 'Modules/Planning/Database/Migrations',
            '--force' => true,
        ]);
    }

    /**
     * Se place dans le contexte d'un front SPA autorisé par Sanctum.
     *
     * EnsureFrontendRequestsAreStateful ne lit que l'en-tête Referer
     * (ou Origin à défaut) : sans lui, aucune session n'est démarrée et
     * aucune protection CSRF n'est appliquée.
     *
     * withCredentials() est indispensable : les helpers *Json() ignorent
     * les cookies du test tant qu'il n'est pas activé.
     */
    protected function asSpaClient(): static
    {
        return $this->withCredentials()
            ->withHeader('Referer', 'http://localhost:3000/dashboard');
    }

    /**
     * Force un pilote de session qui persiste réellement les données.
     *
     * Indispensable pour tester le cookie de session : avec le pilote `array`
     * (défaut de phpunit.xml), les données vivent uniquement dans la mémoire du
     * Store, qui survit d'une requête à l'autre dans un même test — le cookie
     * ne prouverait alors rien.
     */
    protected function usePersistedSession(): static
    {
        config(['session.driver' => 'database']);

        return $this;
    }

    /**
     * Rejoue le cookie de session posé par la réponse comme le ferait le
     * navigateur : la valeur renvoyée par Set-Cookie est déjà chiffrée par
     * EncryptCookies, elle doit donc être renvoyée telle quelle.
     */
    protected function captureSessionCookie(TestResponse $response): static
    {
        $name = (string) config('session.cookie');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $name);

        $this->assertNotNull(
            $cookie,
            "Aucun cookie de session ($name) dans la réponse : le groupe 'api' n'est pas stateful."
        );

        $this->withUnencryptedCookie($name, (string) $cookie->getValue());

        return $this;
    }

    /**
     * Rejoue une session déjà créée par un login antérieur, uniquement via son
     * cookie — l'état du serveur est la seule source de la requête.
     *
     * Contrairement à une suite d'appels sur un même test, ce scénario ne
     * bénéficie d'aucune session en mémoire : StartSession est un singleton du
     * conteneur qui conserve le premier Store résolu, une session « oubliée »
     * rendrait donc possible un faux positif.
     */
    protected function withBrowserSession(User $user): static
    {
        $this->usePersistedSession();

        $id = Str::random(40);
        $loginKey = $this->app['auth']->guard('web')->getName();

        DB::table((string) config('session.table', 'sessions'))->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'payload' => base64_encode(serialize([$loginKey => $user->id])),
            'last_activity' => time(),
        ]);

        $name = (string) config('session.cookie');
        $value = encrypt(
            CookieValuePrefix::create($name, $this->app['encrypter']->getKey()).$id,
            false
        );

        return $this->withUnencryptedCookie($name, $value);
    }

    /**
     * Vide le cache des guards resolus par les appels précédents.
     *
     * Le client de test réutilise le même conteneur : sans cela, RequestGuard
     * (sanctum) comme SessionGuard conserveraient l'utilisateur de la requête
     * précédente, et un logout ne se verrait jamais.
     *
     * À ne pas confondre avec une remise à zéro de la session : ici, le
     * magasin de session doit rester le même, sinon le cookie deviendrait
     * décoratif (StartSession est un singleton qui garde le premier Store).
     */
    protected function forgetResolvedGuards(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    protected function createDefaultRoles(): void
    {
        // Create roles if they don't exist
        $roles = ['super-admin', 'administrateur', 'enseignant', 'etudiant'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
