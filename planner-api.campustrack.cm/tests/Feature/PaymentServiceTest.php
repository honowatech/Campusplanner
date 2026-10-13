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
            '*/api/auth/login' => Http::response(['data' => ['token' => 'jwt-token']], 200),
            '*/api/transaction/init_payment' => Http::response([
                'data' => [
                    'gateway_reference' => $gatewayReference,
                    'external_reference' => 'ext-'.$gatewayReference,
                ],
            ], 200),
        ]);
    }

    private function fakePaymeStatus(string $status): void
    {
        Http::fake([
            '*/api/auth/login' => Http::response(['data' => ['token' => 'jwt-token']], 200),
            '*/api/clients/transaction/payment_status' => Http::response(['data' => ['status' => $status]], 200),
        ]);
    }

    private function pendingPayment(string $slug, string $gatewayReference): Payment
    {
        $tenant = Tenant::firstOrCreate(['slug' => $slug], ['name' => $slug]);

        return Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 60000,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $gatewayReference,
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
    public function successful_payment_activates_the_subscription(): void
    {
        $this->gateway();
        $this->fakePaymeStatus('SUCCESSFUL');

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

        app(PaymentService::class)->reconcilePending();

        $this->assertEquals(1, $tenant->subscriptions()->where('status', 'active')->count());
    }

    /** @test */
    public function reconcile_pending_marks_successful_payment_as_paid(): void
    {
        $this->gateway();
        $this->fakePaymeStatus('SUCCESSFUL');
        $payment = $this->pendingPayment('pay-poll-ok', 'GW-POLL-OK');

        $updated = app(PaymentService::class)->reconcilePending();

        $this->assertEquals(1, $updated);
        $this->assertEquals(PaymentStatus::Paid->value, $payment->fresh()->status);
    }

    /** @test */
    public function reconcile_pending_keeps_payment_in_progress(): void
    {
        $this->gateway();
        $this->fakePaymeStatus('PAYMENT_IN_PROGRESS');
        $payment = $this->pendingPayment('pay-poll-prog', 'GW-POLL-PROG');

        $updated = app(PaymentService::class)->reconcilePending();

        $this->assertEquals(0, $updated);
        $this->assertEquals(PaymentStatus::InProgress->value, $payment->fresh()->status);
    }

    /** @test */
    public function reconcile_pending_marks_failed_payment_as_failed(): void
    {
        $this->gateway();
        $this->fakePaymeStatus('FAILED');
        $payment = $this->pendingPayment('pay-poll-fail', 'GW-POLL-FAIL');

        $updated = app(PaymentService::class)->reconcilePending();

        $this->assertEquals(1, $updated);
        $this->assertEquals(PaymentStatus::Failed->value, $payment->fresh()->status);
    }

    /** @test */
    public function reconcile_pending_marks_stale_payment_for_manual_review(): void
    {
        $this->gateway();
        // Si le paiement périmé était réinterrogé, il recevrait SUCCESSFUL ;
        // il doit rester « à vérifier » sans appel à l'API.
        $this->fakePaymeStatus('SUCCESSFUL');

        $payment = $this->pendingPayment('pay-stale', 'GW-STALE');
        $payment->forceFill(['created_at' => now()->subMinutes(30)])->save();

        $updated = app(PaymentService::class)->reconcilePending();

        $this->assertEquals(1, $updated);
        $this->assertEquals(PaymentStatus::NeedsReview->value, $payment->fresh()->status);
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
