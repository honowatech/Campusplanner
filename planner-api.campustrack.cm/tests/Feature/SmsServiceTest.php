<?php

namespace Tests\Feature;

use App\Models\Pack;
use App\Models\SmsCredential;
use App\Models\SmsLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SmsService;
use App\Services\TenantSubscriptionsService;
use App\Support\FeatureRegistry;
use App\Support\RoleCatalog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();
    }

    private function tenantWithCredential(): Tenant
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'sms-tenant'], ['name' => 'SMS Tenant']);

        SmsCredential::create([
            'tenant_id' => $tenant->id,
            'provider' => 'nexah',
            'user' => 'sms-user',
            'password' => 'secret',
            'sender_id' => 'CAMPUS',
            'is_active' => true,
        ]);

        return $tenant;
    }

    /** @test */
    public function send_creates_a_log_and_calls_nexah(): void
    {
        Http::fake(['*' => Http::response(['status' => '1', 'message_id' => 'msg-123'], 200)]);

        $tenant = $this->tenantWithCredential();
        $log = app(SmsService::class)->send($tenant, '237600000000', 'Bonjour');

        $this->assertEquals(SmsLog::STATUS_SENT, $log->status);
        $this->assertEquals('msg-123', $log->provider_ref);
        Http::assertSentCount(1);
    }

    /** @test */
    public function idempotency_key_replays_the_existing_log(): void
    {
        Http::fake(['*' => Http::response(['status' => '1'], 200)]);

        $tenant = $this->tenantWithCredential();
        $service = app(SmsService::class);

        $first = $service->send($tenant, '237600000001', 'Message', 'idem-1');
        $second = $service->send($tenant, '237600000001', 'Message', 'idem-1');

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, SmsLog::count());
        Http::assertSentCount(1);
    }

    /** @test */
    public function identical_content_is_deduplicated(): void
    {
        Http::fake(['*' => Http::response(['status' => '1'], 200)]);

        $tenant = $this->tenantWithCredential();
        $service = app(SmsService::class);

        $first = $service->send($tenant, '237600000002', 'Dupliqué');
        $second = $service->send($tenant, '237600000002', 'Dupliqué');

        $this->assertEquals($first->id, $second->id);
        Http::assertSentCount(1);
    }

    /** @test */
    public function send_without_active_credential_throws(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'sms-none'], ['name' => 'SMS None']);

        $this->expectException(\RuntimeException::class);

        app(SmsService::class)->send($tenant, '237600000003', 'Hi');
    }

    /** @test */
    public function sms_routes_are_feature_gated(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'sms-gate'], ['name' => 'SMS Gate']);
        $pack = Pack::where('slug', 'starter-monthly')->firstOrFail();
        app(TenantSubscriptionsService::class)->subscribe($tenant, $pack);

        RoleCatalog::seedTenantRoles($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->getJson('/api/sms/settings')->assertForbidden();
    }

    /** @test */
    public function demo_tenant_can_access_sms_settings(): void
    {
        $demo = Tenant::where('is_demo', true)->firstOrFail();

        $admin = User::factory()->create(['tenant_id' => $demo->id]);
        setPermissionsTeamId($demo->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin);
        $this->getJson('/api/sms/settings')->assertOk();
    }
}
