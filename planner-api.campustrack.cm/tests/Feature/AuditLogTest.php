<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\V1\PaymentStatus;
use App\Services\PaymentService;
use App\Services\TenantSubscriptionsService;
use App\Support\FeatureRegistry;
use App\Support\RoleCatalog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();
    }

    /** @test */
    public function payment_checkout_records_audit_logs(): void
    {
        PaymentGateway::create([
            'provider' => 'payme',
            'user_name' => 'merchant',
            'password' => 'secret',
            'mode' => 'sandbox',
            'is_active' => true,
        ]);

        Http::fake([
            '*/auth/login' => Http::response(['token' => 'jwt'], 200),
            '*/transaction/init_payment' => Http::response(['gateway_reference' => 'GW-AUDIT-1'], 200),
        ]);

        $tenant = Tenant::firstOrCreate(['slug' => 'audit-pay'], ['name' => 'Audit Pay']);
        $pack = Pack::where('slug', 'premium-monthly')->firstOrFail();

        app(PaymentService::class)->checkout($tenant, $pack, '237600000000');

        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.created', 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.in_progress']);
    }

    /** @test */
    public function successful_callback_records_payment_paid(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'audit-cb'], ['name' => 'Audit CB']);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 60000,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => 'GW-AUDIT-CB',
        ]);

        app(PaymentService::class)->processCallback([
            'gateway_reference' => 'GW-AUDIT-CB',
            'status' => 'SUCCESSFUL',
        ]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.paid', 'tenant_id' => $tenant->id]);
    }

    /** @test */
    public function subscribe_records_subscription_activated(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'audit-sub'], ['name' => 'Audit Sub']);
        $pack = Pack::where('slug', 'standard-monthly')->firstOrFail();

        app(TenantSubscriptionsService::class)->subscribe($tenant, $pack);

        $this->assertDatabaseHas('audit_logs', ['event' => 'subscription.activated']);
    }

    /** @test */
    public function tenant_creation_records_audit_log(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->postJson('/api/admin/tenants', [
                'name' => 'École Audit',
                'slug' => 'ecole-audit',
                'admin_name' => 'Admin Audit',
                'admin_email' => 'admin@ecole-audit.com',
                'admin_password' => 'password123',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', ['event' => 'tenant.created']);
    }

    /** @test */
    public function audit_logs_endpoint_returns_logs(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'audit-list'], ['name' => 'Audit List']);
        RoleCatalog::seedTenantRoles($tenant->id);

        $pack = Pack::where('slug', 'premium-monthly')->firstOrFail();
        app(TenantSubscriptionsService::class)->subscribe($tenant, $pack);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        $this->actingAs($admin)
            ->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonPath('data.logs.data.0.event', 'subscription.activated');
    }
}
