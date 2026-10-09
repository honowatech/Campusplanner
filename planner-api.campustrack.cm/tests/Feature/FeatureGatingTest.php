<?php

namespace Tests\Feature;

use App\Models\Pack;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSubscriptionsService;
use App\Support\FeatureRegistry;
use App\Support\RoleCatalog;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FeatureGatingTest extends TestCase
{
    private TenantSubscriptionsService $subscriptions;

    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();

        $this->subscriptions = app(TenantSubscriptionsService::class);
    }

    /** @test */
    public function demo_tenant_has_all_features(): void
    {
        $demo = Tenant::where('is_demo', true)->firstOrFail();

        foreach (array_keys(FeatureRegistry::catalog()) as $key) {
            $this->assertTrue(FeatureRegistry::has($demo, $key), "Demo tenant should have [{$key}]");
        }
    }

    /** @test */
    public function tenant_without_subscription_has_only_core_features(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'no-sub'], ['name' => 'No Subscription']);

        $this->assertTrue(FeatureRegistry::has($tenant, 'dashboard'));
        $this->assertTrue(FeatureRegistry::has($tenant, 'scheduling'));
        $this->assertFalse(FeatureRegistry::has($tenant, 'reports'));
        $this->assertFalse(FeatureRegistry::has($tenant, 'sms'));
        $this->assertFalse(FeatureRegistry::has($tenant, 'ai-scheduling'));
    }

    /** @test */
    public function starter_pack_unlocks_core_and_reports_only(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'starter-tenant'], ['name' => 'Starter Tenant']);
        $pack = Pack::where('slug', 'starter-monthly')->firstOrFail();

        $this->subscriptions->subscribe($tenant, $pack);

        $this->assertTrue(FeatureRegistry::has($tenant, 'dashboard'));
        $this->assertTrue(FeatureRegistry::has($tenant, 'reports'));
        $this->assertFalse(FeatureRegistry::has($tenant, 'exports'));
        $this->assertFalse(FeatureRegistry::has($tenant, 'ai-scheduling'));
        $this->assertFalse(FeatureRegistry::has($tenant, 'sms'));
    }

    /** @test */
    public function premium_pack_unlocks_everything(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'premium-tenant'], ['name' => 'Premium Tenant']);
        $pack = Pack::where('slug', 'premium-monthly')->firstOrFail();

        $this->subscriptions->subscribe($tenant, $pack);

        foreach (array_keys(FeatureRegistry::catalog()) as $key) {
            $this->assertTrue(FeatureRegistry::has($tenant, $key), "Premium should have [{$key}]");
        }
    }

    /** @test */
    public function features_endpoint_reports_enabled_flags(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'endpoint-tenant'], ['name' => 'Endpoint Tenant']);
        $pack = Pack::where('slug', 'standard-monthly')->firstOrFail();
        $this->subscriptions->subscribe($tenant, $pack);

        RoleCatalog::seedTenantRoles($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);

        $response = $this->getJson('/api/features')->assertOk();
        $features = collect($response->json('data.features'))->keyBy('key');

        $this->assertTrue($features['dashboard']['enabled']);
        $this->assertTrue($features['ai-scheduling']['enabled']); // standard inclut l'IA
        $this->assertFalse($features['sms']['enabled']);
    }

    /** @test */
    public function billing_subscription_endpoint_returns_current_subscription(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'billing-tenant'], ['name' => 'Billing Tenant']);
        $pack = Pack::where('slug', 'premium-annual')->firstOrFail();
        $this->subscriptions->subscribe($tenant, $pack);

        RoleCatalog::seedTenantRoles($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);

        $response = $this->getJson('/api/billing/subscription')->assertOk();
        $subscription = $response->json('data.subscription');

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals('premium', $subscription['pack']['tier']);
    }

    /** @test */
    public function feature_middleware_blocks_tenants_without_the_feature(): void
    {
        Route::middleware(['auth:sanctum', 'feature:sms'])
            ->get('/_test/sms', fn () => response()->json(['ok' => true]));

        $tenant = Tenant::firstOrCreate(['slug' => 'gate-tenant'], ['name' => 'Gate Tenant']);
        $pack = Pack::where('slug', 'starter-monthly')->firstOrFail();
        $this->subscriptions->subscribe($tenant, $pack);

        RoleCatalog::seedTenantRoles($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->getJson('/_test/sms')->assertForbidden();
    }

    /** @test */
    public function feature_middleware_allows_demo_tenant(): void
    {
        Route::middleware(['auth:sanctum', 'feature:sms'])
            ->get('/_test/sms-allow', fn () => response()->json(['ok' => true]));

        $demo = Tenant::where('is_demo', true)->firstOrFail();
        $admin = User::factory()->create(['tenant_id' => $demo->id]);
        setPermissionsTeamId($demo->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->getJson('/_test/sms-allow')->assertOk()->assertJson(['ok' => true]);
    }
}
