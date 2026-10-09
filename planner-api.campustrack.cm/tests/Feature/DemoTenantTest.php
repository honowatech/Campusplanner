<?php

namespace Tests\Feature;

use App\Models\Pack;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\FeatureRegistry;
use App\Support\RoleCatalog;
use Tests\TestCase;

class DemoTenantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();
    }

    /** @test */
    public function demo_mode_lives_on_the_tenant(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $demo = Tenant::where('is_demo', true)->firstOrFail();
        $demo->update(['demo_mode' => false]);

        $this->actingAs($superAdmin)
            ->putJson('/api/demo-mode', ['demo_mode' => true])
            ->assertOk();

        $this->assertTrue($demo->fresh()->demo_mode);
    }

    /** @test */
    public function demo_login_uses_the_demo_tenant(): void
    {
        $demo = Tenant::where('is_demo', true)->firstOrFail();
        $demo->update(['demo_mode' => true]);

        $demoUser = User::factory()->create([
            'tenant_id' => $demo->id,
            'is_demo' => true,
            'is_approved' => true,
        ]);
        setPermissionsTeamId($demo->id);
        $demoUser->assignRole('professeur');

        $this->asSpaClient()
            ->postJson('/api/demo-login', ['role' => 'professeur'])
            ->assertOk();
    }

    /** @test */
    public function delete_requests_are_blocked_on_demo_tenant(): void
    {
        $demo = Tenant::where('is_demo', true)->firstOrFail();

        $admin = User::factory()->create(['tenant_id' => $demo->id]);
        setPermissionsTeamId($demo->id);
        $admin->assignRole('administrateur');

        $room = Room::factory()->create(['tenant_id' => $demo->id]);

        $this->actingAs($admin)
            ->deleteJson('/api/rooms/'.$room->id)
            ->assertForbidden();
    }

    /** @test */
    public function real_payments_are_blocked_on_demo_tenant(): void
    {
        $demo = Tenant::where('is_demo', true)->firstOrFail();
        $pack = Pack::where('slug', 'premium-monthly')->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('démo');

        app(PaymentService::class)->checkout($demo, $pack, '237600000000');
    }

    /** @test */
    public function non_demo_tenants_can_still_delete(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'real-tenant'], ['name' => 'Real Tenant']);
        RoleCatalog::seedTenantRoles($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        $room = Room::factory()->create(['tenant_id' => $tenant->id]);

        // Le DELETE atteint le contrôleur (suppression effective) : 200.
        $this->actingAs($admin)
            ->deleteJson('/api/rooms/'.$room->id)
            ->assertOk();
    }
}
