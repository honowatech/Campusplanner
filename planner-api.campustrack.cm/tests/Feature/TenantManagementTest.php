<?php

namespace Tests\Feature;

use App\Models\Pack;
use App\Models\Tenant;
use App\Models\User;
use App\Support\FeatureRegistry;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function tenantPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'École Test',
            'slug' => 'ecole-test',
            'admin_name' => 'Admin École',
            'admin_email' => 'admin@ecole-test.com',
            'admin_password' => 'password123',
        ], $overrides);
    }

    /**
     * Recherche un utilisateur hors scope tenant (contexte de test).
     */
    private function findUser(string $email): ?User
    {
        return User::withoutGlobalScopes()->where('email', $email)->first();
    }

    /** @test */
    public function super_admin_creates_tenant_with_roles_admin_and_trial(): void
    {
        $this->actingAs($this->superAdmin());

        $response = $this->postJson('/api/admin/tenants', $this->tenantPayload())
            ->assertStatus(201);

        $tenantId = $response->json('data.tenant.id');

        // Rôles par tenant seedés.
        $this->assertGreaterThan(0, Role::where('tenant_id', $tenantId)->count());

        // Compte admin initial.
        $admin = $this->findUser('admin@ecole-test.com');
        $this->assertNotNull($admin);
        $this->assertEquals($tenantId, $admin->tenant_id);

        // Essai gratuit.
        $tenant = Tenant::find($tenantId);
        $this->assertTrue($tenant->subscriptions()->where('status', 'trial')->exists());
    }

    /** @test */
    public function public_onboarding_creates_a_tenant(): void
    {
        $this->postJson('/api/onboarding', $this->tenantPayload(['slug' => 'ecole-onboarding']))
            ->assertStatus(201);

        $this->assertNotNull(Tenant::where('slug', 'ecole-onboarding')->first());
        $this->assertNotNull($this->findUser('admin@ecole-test.com'));
    }

    /** @test */
    public function suspended_tenant_users_are_blocked(): void
    {
        $this->actingAs($this->superAdmin());

        $response = $this->postJson('/api/admin/tenants', $this->tenantPayload(['slug' => 'ecole-suspend']))
            ->assertStatus(201);

        $tenantId = $response->json('data.tenant.id');

        $this->postJson("/api/admin/tenants/{$tenantId}/suspend")->assertOk();

        $admin = $this->findUser('admin@ecole-test.com');

        $this->actingAs($admin);
        $this->getJson('/api/auth-user')->assertForbidden();
    }

    /** @test */
    public function super_admin_configures_payment_gateway(): void
    {
        $this->actingAs($this->superAdmin());

        $this->putJson('/api/admin/payment-gateway', [
            'user_name' => 'merchant',
            'password' => 'secret',
            'mode' => 'sandbox',
            'is_active' => true,
        ])->assertOk();
    }

    /** @test */
    public function super_admin_can_toggle_a_feature(): void
    {
        $this->actingAs($this->superAdmin());

        $feature = \App\Models\Feature::where('key', 'sms')->firstOrFail();

        $this->putJson("/api/admin/features/{$feature->id}", ['is_active' => false])
            ->assertOk();

        $this->assertFalse($feature->fresh()->is_active);
    }
}
