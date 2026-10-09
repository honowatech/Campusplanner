<?php

namespace Tests\Feature;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Services\PaymentService;
use App\Support\FeatureRegistry;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();
    }

    private function gateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'provider' => 'payme',
            'user_name' => 'merchant',
            'password' => 'secret',
            'mode' => 'sandbox',
            'is_active' => true,
        ]);
    }

    private function pack(string $slug = 'premium-monthly'): Pack
    {
        return Pack::where('slug', $slug)->firstOrFail();
    }

    private function fakePaymeInitiate(string $gatewayReference = 'GW-123'): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['token' => 'jwt-token'], 200),
            '*/transaction/init_payment' => Http::response(['gateway_reference' => $gatewayReference], 200),
        ]);
    }

    /** @test */
    public function checkout_creates_an_in_progress_payment(): void
    {
        $this->gateway();
        $this->fakePaymeInitiate();

        $tenant = Tenant::firstOrCreate(['slug' => 'pay-tenant'], ['name' => 'Pay Tenant']);
        $payment = app(PaymentService::class)->checkout($tenant, $this->pack(), '237600000000');

        $this->assertEquals(PaymentStatus::InProgress->value, $payment->status);
        $this->assertEquals('GW-123', $payment->gateway_reference);
    }

    /** @test */
    public function checkout_is_idempotent_via_idempotency_key(): void
    {
        $this->gateway();
        $this->fakePaymeInitiate();

        $tenant = Tenant::firstOrCreate(['slug' => 'pay-idem'], ['name' => 'Pay Idem']);
        $service = app(PaymentService::class);

        $first = $service->checkout($tenant, $this->pack(), '237600000001', null, 'idem-pay-1');
        $second = $service->checkout($tenant, $this->pack(), '237600000001', null, 'idem-pay-1');

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Payment::count());
    }

    /** @test */
    public function process_callback_is_idempotent(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'pay-cb'], ['name' => 'Pay CB']);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 60000,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => 'GW-CB-1',
        ]);

        $payload = ['gateway_reference' => 'GW-CB-1', 'status' => 'SUCCESSFUL'];
        $service = app(PaymentService::class);

        $first = $service->processCallback($payload);
        $second = $service->processCallback($payload);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertEquals(PaymentStatus::Paid->value, $payment->fresh()->status);
    }

    /** @test */
    public function successful_callback_activates_the_subscription(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'pay-activate'], ['name' => 'Pay Activate']);
        $pack = $this->pack('standard-annual');

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => $pack->price,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => 'GW-ACT-1',
            'metadata' => ['pack_id' => $pack->id, 'billing_period' => 'annual'],
        ]);

        app(PaymentService::class)->processCallback([
            'gateway_reference' => 'GW-ACT-1',
            'status' => 'SUCCESSFUL',
        ]);

        $this->assertEquals(1, $tenant->subscriptions()->where('status', 'active')->count());
    }

    /** @test */
    public function illegal_transition_is_a_no_op(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'pay-state'], ['name' => 'Pay State']);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 1000,
            'currency' => 'XAF',
            'status' => PaymentStatus::Paid->value,
        ]);

        // paid → pending (retour arrière) est illégal.
        $service = app(PaymentService::class);
        $result = $service->transition($payment, PaymentStatus::Pending);

        $this->assertEquals(PaymentStatus::Paid->value, $result->status);

        // paid → refunded est légal.
        $result = $service->transition($payment, PaymentStatus::Refunded);

        $this->assertEquals(PaymentStatus::Refunded->value, $result->status);
    }
}
