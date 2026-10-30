<?php

namespace Tests\Feature;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymeClient;
use App\Services\PaymentService;
use App\Support\FeatureRegistry;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Paiements PayMe simulés sur le contrat réel de l'API Partenaire
 * (docs/FEUILLE_DE_ROUTE_PAYME.md § 0 et § 5).
 */
class PaymentServiceTest extends TestCase
{
    private const API = 'https://console-v2.mamonipay.me/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();
    }

    private function gateway(array $overrides = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'provider' => 'payme',
            'user_name' => '237600000000',
            'password' => 'secret',
            'is_active' => true,
        ], $overrides));
    }

    private function pack(string $slug = 'premium-monthly'): Pack
    {
        return Pack::where('slug', $slug)->firstOrFail();
    }

    private function tenant(string $slug = 'pay-tenant'): Tenant
    {
        return Tenant::firstOrCreate(['slug' => $slug], ['name' => 'École '.$slug]);
    }

    private function loginResponse(): PromiseInterface
    {
        return Http::response([
            'success' => true,
            'data' => ['access_token' => 'jwt-token', 'expires_at' => now()->addHour()->toIso8601String()],
        ], 200);
    }

    /**
     * @param  array<string, mixed>  $responses  motif d'URL relatif => réponse
     */
    private function fakePayme(array $responses): void
    {
        $map = ['*/v1/auth/login' => $this->loginResponse()];

        foreach ($responses as $pattern => $response) {
            $map[$pattern] = $response;
        }

        Http::fake($map);
    }

    private function initOk(string $reference = 'gw-uuid-1', string $status = 'Initiated'): PromiseInterface
    {
        return Http::response([
            'success' => true,
            'data' => ['gateway_reference' => $reference, 'status' => $status, 'payment_provider_slug' => 'mtn'],
        ], 200);
    }

    private function verifyStatus(string $status, array $extra = []): PromiseInterface
    {
        return Http::response([
            'success' => true,
            'data' => array_merge(['status' => $status, 'payment_provider_slug' => 'orange'], $extra),
        ], 200);
    }

    private function inProgressPayment(string $reference = 'gw-uuid-1', ?Pack $pack = null, array $overrides = []): Payment
    {
        $pack ??= $this->pack();

        return Payment::create(array_merge([
            'tenant_id' => $this->tenant()->id,
            'amount' => $pack->price,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $reference,
            'external_reference' => 'ext-'.$reference,
            'metadata' => ['pack_id' => $pack->id, 'billing_period' => $pack->billing_period],
        ], $overrides));
    }

    // ── 1. Connexion ──────────────────────────────────────────────────────

    /** @test */
    public function login_uses_real_contract_and_token_is_reused(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/verify/*' => $this->verifyStatus('Payment in progress')]);

        $this->inProgressPayment('gw-a');
        $this->inProgressPayment('gw-b', null, ['tenant_id' => $this->tenant('other')->id]);

        app(PaymentService::class)->reconcilePending();

        Http::assertSent(fn (Request $r) => $r->url() === self::API.'/auth/login'
            && $r['login'] === '237600000000'
            && $r['password'] === 'secret');

        $logins = collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/auth/login'));
        $this->assertCount(1, $logins);
    }

    /** @test */
    public function token_is_renewed_once_on_401(): void
    {
        $gateway = $this->gateway();

        Http::fake([
            '*/v1/auth/login' => $this->loginResponse(),
            '*/v1/payments/verify/*' => Http::sequence()
                ->push(['message' => 'Unauthenticated.'], 401)
                ->push(['success' => true, 'data' => ['status' => 'Successful']], 200),
        ]);

        $result = app(PaymeClient::class)->paymentStatus($gateway, 'gw-401');

        $this->assertSame('success', $result['state']);
        $logins = collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/auth/login'));
        $this->assertCount(2, $logins);
    }

    // ── 2. Connexion refusée ─────────────────────────────────────────────

    /** @test */
    public function refused_login_in_http_200_reports_message_and_url(): void
    {
        $gateway = $this->gateway();
        Http::fake(['*/v1/auth/login' => Http::response(['success' => false, 'message' => 'Identifiants invalides'], 200)]);

        try {
            app(PaymeClient::class)->token($gateway);
            $this->fail('Une exception était attendue.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Identifiants invalides', $e->getMessage());
            $this->assertStringContainsString(self::API.'/auth/login', $e->getMessage());
        }
    }

    // ── 3. Initiation ────────────────────────────────────────────────────

    /** @test */
    public function checkout_sends_the_real_payload_and_goes_in_progress(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/init' => $this->initOk('gw-init')]);

        $payment = app(PaymentService::class)->checkout($this->tenant(), $this->pack(), '6 99 00 00 00');

        $this->assertSame(PaymentStatus::InProgress->value, $payment->status);
        $this->assertSame('gw-init', $payment->gateway_reference);
        $this->assertSame('MTN', $payment->provider);

        Http::assertSent(function (Request $r) use ($payment) {
            return $r->url() === self::API.'/payments/init'
                && $r->hasHeader('Authorization', 'Bearer jwt-token')
                && $r['channel'] === 'SELLER_DIRECT'
                && $r['phone_number'] === '237699000000'
                && $r['amount'] === 60000
                && $r['external_reference'] === $payment->external_reference
                && $r['client_name'] === 'École pay-tenant'
                && ! array_key_exists('client_fees_rate', $r->data());
        });
    }

    /** @test */
    public function client_fees_rate_is_sent_only_when_configured(): void
    {
        $this->gateway(['client_fees_rate' => 50]);
        $this->fakePayme(['*/v1/payments/init' => $this->initOk()]);

        app(PaymentService::class)->checkout($this->tenant(), $this->pack(), '237699000000');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/payments/init') && $r['client_fees_rate'] === 50);
    }

    /** @test */
    public function checkout_is_idempotent_via_idempotency_key(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/init' => $this->initOk()]);

        $service = app(PaymentService::class);
        $first = $service->checkout($this->tenant(), $this->pack(), '237600000001', null, 'idem-pay-1');
        $second = $service->checkout($this->tenant(), $this->pack(), '237600000001', null, 'idem-pay-1');

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Payment::count());
    }

    /** @test */
    public function a_second_checkout_is_refused_while_one_is_awaiting_validation(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/init' => $this->initOk()]);

        $service = app(PaymentService::class);
        $service->checkout($this->tenant(), $this->pack(), '237600000001');

        $this->expectException(\RuntimeException::class);
        $service->checkout($this->tenant(), $this->pack(), '237600000001');
    }

    // ── 4. / 5. Refus métier et validation ───────────────────────────────

    /** @test */
    public function business_refusal_in_http_200_fails_the_payment(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/init' => Http::response(['success' => false, 'message' => 'INSUFFICIENT_FUNDS'], 200)]);

        $payment = app(PaymentService::class)->checkout($this->tenant(), $this->pack(), '237600000000');

        $this->assertSame(PaymentStatus::Failed->value, $payment->status);
        $this->assertNull($payment->gateway_reference);
        $this->assertSame('INSUFFICIENT_FUNDS', $payment->metadata['error']);
    }

    /** @test */
    public function validation_error_422_fails_with_field_details(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/init' => Http::response([
            'code' => 'VAL-001',
            'message' => 'Erreur de validation',
            'errors' => ['message' => 'Erreur de validation', 'errors' => ['amount' => ['the amount field must be at least 10.']]],
        ], 422)]);

        $payment = app(PaymentService::class)->checkout($this->tenant(), $this->pack(), '237600000000');

        $this->assertSame(PaymentStatus::Failed->value, $payment->status);
        $this->assertSame('The amount field must be at least 10.', $payment->metadata['error']);
    }

    // ── 6. / 7. Vérification ─────────────────────────────────────────────

    /** @test */
    public function successful_verification_activates_the_subscription(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/verify/*' => $this->verifyStatus('Successful', [
            'receipt' => ['receipt_url' => 'https://console-v2.mamonipay.me/receipts/1'],
        ])]);

        $pack = $this->pack('standard-annual');
        $payment = $this->inProgressPayment('gw-ok', $pack);

        $this->assertSame(1, app(PaymentService::class)->reconcilePending());

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid->value, $payment->status);
        $this->assertSame('https://console-v2.mamonipay.me/receipts/1', $payment->receipt_url);
        $this->assertSame('ORANGE', $payment->provider);
        $this->assertNotNull($payment->subscription_id);

        $tenant = $this->tenant();
        $subscription = $tenant->subscriptions()->where('status', 'active')->sole();
        $this->assertSame($pack->id, $subscription->pack_id);
        $this->assertTrue(FeatureRegistry::has($tenant->fresh(), 'ai-scheduling'));

        Http::assertSent(fn (Request $r) => $r->url() === self::API.'/payments/verify/gw-ok' && $r->method() === 'POST');
    }

    /** @test */
    public function in_progress_status_and_409_leave_the_payment_unchanged(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/verify/*' => Http::sequence()
            ->push(['success' => true, 'data' => ['status' => 'Payment in progress']], 200)
            ->push(['message' => 'Transaction en cours'], 409)]);

        $payment = $this->inProgressPayment('gw-wait');
        $service = app(PaymentService::class);

        $this->assertSame(PaymentStatus::InProgress->value, $service->verify($payment)->status);
        $this->assertSame(PaymentStatus::InProgress->value, $service->verify($payment->fresh())->status);
        $this->assertSame(2, $payment->fresh()->checks);
    }

    /** @test */
    public function cancelled_or_expired_status_fails_the_payment(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/verify/*' => $this->verifyStatus('Cancelled')]);

        $payment = $this->inProgressPayment('gw-cancelled');

        $this->assertSame(1, app(PaymentService::class)->reconcilePending());
        $this->assertSame(PaymentStatus::Failed->value, $payment->fresh()->status);
        $this->assertSame('Cancelled', $payment->fresh()->gateway_status);
    }

    /** @test */
    public function the_same_payment_confirmed_twice_activates_only_one_period(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/verify/*' => $this->verifyStatus('Successful')]);

        $payment = $this->inProgressPayment('gw-twice');
        $stale = Payment::find($payment->id);
        $service = app(PaymentService::class);

        $service->verify($payment);
        // Seconde confirmation concurrente sur une copie périmée du modèle.
        (fn () => $this->markPaid($stale, PaymentStatus::Paid))->call($service);

        $this->assertSame(1, $this->tenant()->subscriptions()->where('status', 'active')->count());
    }

    // ── 8. / 9. Sans référence et expiration ─────────────────────────────

    /** @test */
    public function payment_without_reference_is_never_verified_and_fails_after_two_minutes(): void
    {
        $this->gateway();
        $this->fakePayme([]);

        $payment = $this->inProgressPayment('unused', null, ['gateway_reference' => null, 'status' => PaymentStatus::Pending->value]);
        $service = app(PaymentService::class);

        $service->reconcilePending();
        $this->assertSame(PaymentStatus::Pending->value, $payment->fresh()->status);

        $this->travel(3)->minutes();
        $service->reconcilePending();

        $this->assertSame(PaymentStatus::Failed->value, $payment->fresh()->status);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/payments/verify/'));
    }

    /** @test */
    public function pending_payment_expires_after_the_configured_timeout(): void
    {
        $this->gateway(['timeout_minutes' => 3]);
        $this->fakePayme(['*/v1/payments/verify/*' => $this->verifyStatus('Payment in progress')]);

        $payment = $this->inProgressPayment('gw-expire', null, ['metadata' => ['timeout_minutes' => 3]]);
        $service = app(PaymentService::class);

        $this->travel(2)->minutes();
        $service->reconcilePending();
        $this->assertSame(PaymentStatus::InProgress->value, $payment->fresh()->status);

        $this->travel(2)->minutes();
        $service->reconcilePending();
        $this->assertSame(PaymentStatus::Failed->value, $payment->fresh()->status);
        $this->assertSame('EXPIRED', $payment->fresh()->gateway_status);
    }

    // ── 10. Annulation puis validation tardive ───────────────────────────

    /** @test */
    public function cancelled_payment_validated_late_is_recovered_and_activates(): void
    {
        $this->gateway();
        $this->fakePayme(['*/v1/payments/verify/*' => Http::sequence()
            ->push(['success' => true, 'data' => ['status' => 'Payment in progress']], 200)
            ->push(['success' => true, 'data' => ['status' => 'Successful']], 200)]);

        $payment = $this->inProgressPayment('gw-late');
        $service = app(PaymentService::class);

        $cancelled = $service->cancel($payment);
        $this->assertSame(PaymentStatus::Failed->value, $cancelled->status);
        $this->assertSame('CANCELLED', $cancelled->gateway_status);

        $this->travel(10)->minutes();
        $service->reconcilePending();

        $this->assertSame(PaymentStatus::Paid->value, $payment->fresh()->status);
        $this->assertSame(1, $this->tenant()->subscriptions()->where('status', 'active')->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.recovered']);
    }

    // ── 11. Tenant démo ─────────────────────────────────────────────────

    /** @test */
    public function checkout_is_refused_on_the_demo_tenant(): void
    {
        $this->gateway();

        $this->expectException(\RuntimeException::class);

        app(PaymentService::class)->checkout(Tenant::where('is_demo', true)->firstOrFail(), $this->pack(), '237600000000');
    }

    // ── 12. Configuration ───────────────────────────────────────────────

    /** @test */
    public function gateway_configuration_is_validated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $this->putJson('/api/admin/payment-gateway', ['user_name' => 'm', 'endpoint' => 'https://console-v2.mamonipay.me/docs/api-json'])
            ->assertStatus(422);
        $this->putJson('/api/admin/payment-gateway', ['user_name' => 'm', 'endpoint' => 'https://console-v2.mamonipay.me/api/v1'])
            ->assertStatus(422);
        $this->putJson('/api/admin/payment-gateway', ['user_name' => 'm', 'client_fees_rate' => 150])
            ->assertStatus(422);
        $this->putJson('/api/admin/payment-gateway', ['user_name' => 'm', 'timeout_minutes' => 1])
            ->assertStatus(422);

        $this->putJson('/api/admin/payment-gateway', [
            'user_name' => 'merchant',
            'password' => 'secret',
            'endpoint' => 'https://console-v2.mamonipay.me/api',
            'timeout_minutes' => 10,
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('data.gateway.timeout_minutes', 10)
            ->assertJsonMissingPath('data.gateway.password');
    }

    /** @test */
    public function connection_test_reports_the_called_url_on_failure(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->gateway();

        Http::fake(['*/v1/auth/login' => Http::response(['message' => 'Not Found'], 404)]);

        $this->actingAs($admin)
            ->postJson('/api/admin/payment-gateway/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.url', self::API.'/auth/login');
    }

    /** @test */
    public function illegal_transition_is_a_no_op(): void
    {
        $payment = Payment::create([
            'tenant_id' => $this->tenant('pay-state')->id,
            'amount' => 1000,
            'currency' => 'XAF',
            'status' => PaymentStatus::Paid->value,
        ]);

        $service = app(PaymentService::class);

        $this->assertEquals(PaymentStatus::Paid->value, $service->transition($payment, PaymentStatus::Pending)->status);
        $this->assertEquals(PaymentStatus::Refunded->value, $service->transition($payment, PaymentStatus::Refunded)->status);
    }
}
