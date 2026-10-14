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
 * Simule le contrat réel de l'API Partenaire PayMe
 * (https://console-v2.mamonipay.me/api, cf. docs/FEUILLE_DE_ROUTE_PAYME.md §0).
 */
class PaymentServiceTest extends TestCase
{
    private const BASE = 'https://console-v2.mamonipay.me/api';

    private const LOGIN = self::BASE.'/v1/auth/login';

    private const INIT = self::BASE.'/v1/payments/init';

    private const VERIFY = self::BASE.'/v1/payments/verify/*';

    private const REF = '9b2f1c7e-3d4a-4f5b-8c6d-7e8f9a0b1c2d';

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
            'user_name' => '237690000000',
            'password' => 'secret',
            'is_active' => true,
        ], $overrides));
    }

    private function pack(string $slug = 'premium-monthly'): Pack
    {
        return Pack::where('slug', $slug)->firstOrFail();
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::firstOrCreate(['slug' => $slug], ['name' => 'Tenant '.$slug]);
    }

    private function loginResponse(string $token = 'jwt-1'): PromiseInterface
    {
        return Http::response([
            'success' => true,
            'message' => 'OK',
            'data' => ['access_token' => $token, 'expires_at' => now()->addHour()->toIso8601String()],
            'status' => 200,
        ], 200);
    }

    private function initResponse(string $status = 'Initiated', string $reference = self::REF): PromiseInterface
    {
        return Http::response([
            'success' => true,
            'message' => 'Transaction initiée',
            'data' => ['gateway_reference' => $reference, 'status' => $status, 'payment_provider_slug' => 'orange'],
            'status' => 200,
        ], 200);
    }

    private function verifyResponse(string $status, int $http = 200, array $extra = []): PromiseInterface
    {
        return Http::response([
            'success' => true,
            'message' => 'OK',
            'data' => array_merge(['gateway_reference' => self::REF, 'status' => $status, 'payment_provider_slug' => 'mtn'], $extra),
            'status' => $http,
        ], $http);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function inProgressPayment(string $slug, ?string $reference = self::REF, array $overrides = []): Payment
    {
        $tenant = $this->tenant($slug);
        $pack = $this->pack('standard-annual');

        return Payment::create(array_merge([
            'tenant_id' => $tenant->id,
            'amount' => $pack->price,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $reference,
            'external_reference' => 'ext-'.$slug,
            'metadata' => ['pack_id' => $pack->id, 'billing_period' => 'annual'],
        ], $overrides));
    }

    // ── 1. Connexion ──────────────────────────────────────────────────────

    /** @test */
    public function login_sends_login_and_password_and_reuses_the_token(): void
    {
        $gateway = $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse('jwt-cached'),
            self::VERIFY => $this->verifyResponse('Payment in progress'),
        ]);

        $client = app(PaymeClient::class);
        $client->paymentStatus($gateway, self::REF);
        $client->paymentStatus($gateway, self::REF);

        Http::assertSent(fn (Request $r) => $r->url() === self::LOGIN
            && $r['login'] === '237690000000'
            && $r['password'] === 'secret'
            && ! isset($r['user_name']));
        Http::assertSentCount(3); // 1 login + 2 verify
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/verify/') && $r->hasHeader('Authorization', 'Bearer jwt-cached'));
    }

    /** @test */
    public function token_is_renewed_once_on_401(): void
    {
        $gateway = $this->gateway();
        Http::fake([
            self::LOGIN => Http::sequence()->push(['data' => ['access_token' => 'jwt-old']])->push(['data' => ['access_token' => 'jwt-new']]),
            self::VERIFY => Http::sequence()
                ->push(['message' => 'Unauthenticated.'], 401)
                ->push(['success' => true, 'data' => ['status' => 'Successful']]),
        ]);

        $result = app(PaymeClient::class)->paymentStatus($gateway, self::REF);

        $this->assertEquals('success', $result['state']);
        Http::assertSentCount(4);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/verify/') && $r->hasHeader('Authorization', 'Bearer jwt-new'));
    }

    // ── 2. Connexion refusée ──────────────────────────────────────────────

    /** @test */
    public function refused_login_in_http_200_raises_with_message_and_url(): void
    {
        $gateway = $this->gateway();
        Http::fake([
            self::LOGIN => Http::response(['success' => false, 'message' => 'Identifiants invalides', 'data' => null], 200),
        ]);

        try {
            app(PaymeClient::class)->paymentStatus($gateway, self::REF);
            $this->fail('Une exception était attendue.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Identifiants invalides', $e->getMessage());
            $this->assertStringContainsString(self::LOGIN, $e->getMessage());
        }
    }

    // ── 3. Initiation ─────────────────────────────────────────────────────

    /** @test */
    public function checkout_sends_the_real_init_payload_and_moves_to_in_progress(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::INIT => $this->initResponse()]);

        $tenant = $this->tenant('pay-tenant');
        $pack = $this->pack();
        $payment = app(PaymentService::class)->checkout($tenant, $pack, '+237 6 90 00 00 00');

        Http::assertSent(fn (Request $r) => $r->url() === self::INIT
            && $r['channel'] === 'SELLER_DIRECT'
            && $r['phone_number'] === '237690000000'
            && $r['amount'] === (int) round($pack->price)
            && $r['external_reference'] === $payment->external_reference
            && $r['client_name'] === $tenant->name
            && ! array_key_exists('client_fees_rate', $r->data()));

        $this->assertEquals(PaymentStatus::InProgress->value, $payment->status);
        $this->assertEquals(self::REF, $payment->gateway_reference);
        $this->assertEquals('Initiated', $payment->gateway_status);
        $this->assertEquals('ORANGE', $payment->provider);
        $this->assertNotNull($payment->metadata['expires_at']);
    }

    /** @test */
    public function client_fees_rate_is_sent_only_when_configured(): void
    {
        $this->gateway(['client_fees_rate' => 0]);
        Http::fake([self::LOGIN => $this->loginResponse(), self::INIT => $this->initResponse()]);

        app(PaymentService::class)->checkout($this->tenant('pay-fees'), $this->pack(), '237690000001');

        Http::assertSent(fn (Request $r) => $r->url() === self::INIT && $r['client_fees_rate'] === 0);
    }

    /** @test */
    public function checkout_is_idempotent_via_idempotency_key(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::INIT => $this->initResponse()]);

        $tenant = $this->tenant('pay-idem');
        $service = app(PaymentService::class);

        $first = $service->checkout($tenant, $this->pack(), '237600000001', null, 'idem-pay-1');
        $second = $service->checkout($tenant, $this->pack(), '237600000001', null, 'idem-pay-1');

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Payment::count());
    }

    // ── 4. Refus métier en HTTP 200 ───────────────────────────────────────

    /** @test */
    public function business_refusal_in_http_200_fails_the_payment(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::INIT => Http::response(['success' => false, 'message' => 'INSUFFICIENT_FUNDS', 'data' => null, 'status' => 200], 200),
        ]);

        $payment = app(PaymentService::class)->checkout($this->tenant('pay-refused'), $this->pack(), '237690000002');

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertNull($payment->gateway_reference);
        $this->assertEquals('INSUFFICIENT_FUNDS', $payment->metadata['error']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.failed', 'auditable_id' => $payment->id]);
    }

    // ── 5. Erreur de validation 422 ───────────────────────────────────────

    /** @test */
    public function validation_error_422_fails_with_field_detail(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::INIT => Http::response([
                'code' => 'VAL-001',
                'message' => 'Erreur de validation',
                'errors' => ['message' => 'Erreur de validation', 'errors' => ['amount' => ['the amount field must be at least 10.']]],
            ], 422),
        ]);

        $payment = app(PaymentService::class)->checkout($this->tenant('pay-422'), $this->pack(), '237690000003');

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals('The amount field must be at least 10.', $payment->metadata['error']);
    }

    /** @test */
    public function init_without_gateway_reference_fails_the_payment(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::INIT => Http::response(['success' => true, 'data' => ['status' => 'Initiated']], 200),
        ]);

        $payment = app(PaymentService::class)->checkout($this->tenant('pay-noref'), $this->pack(), '237690000004');

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertStringContainsString('référence', $payment->metadata['error']);
    }

    // ── 6. Vérification ───────────────────────────────────────────────────

    /** @test */
    public function successful_verification_marks_paid_activates_subscription_and_stores_receipt(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::VERIFY => $this->verifyResponse('Successful', 200, [
                'processed_at' => now()->toIso8601String(),
                'receipt' => ['receipt_url' => 'https://console-v2.mamonipay.me/receipts/abc.pdf'],
            ]),
        ]);

        $payment = $this->inProgressPayment('pay-success');

        $updated = app(PaymentService::class)->reconcilePending();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === self::BASE.'/v1/payments/verify/'.self::REF);

        $payment->refresh();
        $this->assertEquals(1, $updated);
        $this->assertEquals(PaymentStatus::Paid->value, $payment->status);
        $this->assertEquals('Successful', $payment->gateway_status);
        $this->assertEquals('MTN', $payment->provider);
        $this->assertEquals('https://console-v2.mamonipay.me/receipts/abc.pdf', $payment->receipt_url);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->subscription_id);
        $this->assertEquals(1, $payment->tenant->subscriptions()->where('status', 'active')->count());
    }

    /** @test */
    public function payment_in_progress_status_keeps_the_payment_unchanged(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::VERIFY => $this->verifyResponse('Payment in progress')]);
        $payment = $this->inProgressPayment('pay-progress');

        $updated = app(PaymentService::class)->reconcilePending();

        $payment->refresh();
        $this->assertEquals(0, $updated);
        $this->assertEquals(PaymentStatus::InProgress->value, $payment->status);
        $this->assertEquals('Payment in progress', $payment->gateway_status);
        $this->assertEquals(1, $payment->checks);
        $this->assertNotNull($payment->checked_at);
    }

    private function assertTerminalStatusFails(string $status): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::VERIFY => $this->verifyResponse($status)]);
        $payment = $this->inProgressPayment('pay-'.strtolower($status));

        $updated = app(PaymentService::class)->reconcilePending();

        $payment->refresh();
        $this->assertEquals(1, $updated);
        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals($status, $payment->gateway_status);
        $this->assertStringContainsString('failed_at', json_encode($payment->metadata));
    }

    /** @test */
    public function cancelled_status_fails_the_payment(): void
    {
        $this->assertTerminalStatusFails('Cancelled');
    }

    /** @test */
    public function expired_status_fails_the_payment(): void
    {
        $this->assertTerminalStatusFails('Expired');
    }

    /** @test */
    public function failed_status_fails_the_payment(): void
    {
        $this->assertTerminalStatusFails('Failed');
    }

    // ── 7. 409 ────────────────────────────────────────────────────────────

    /** @test */
    public function http_409_on_verification_keeps_the_payment_in_progress(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::VERIFY => Http::response(['success' => false, 'message' => 'Transaction en cours'], 409),
        ]);
        $payment = $this->inProgressPayment('pay-409');

        $updated = app(PaymentService::class)->reconcilePending();

        $this->assertEquals(0, $updated);
        $this->assertEquals(PaymentStatus::InProgress->value, $payment->fresh()->status);
    }

    /** @test */
    public function http_404_on_verification_does_not_stop_the_loop(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::BASE.'/v1/payments/verify/unknown' => Http::response(['code' => 'TXN-002', 'message' => 'Transaction introuvable'], 404),
            self::BASE.'/v1/payments/verify/'.self::REF => $this->verifyResponse('Successful'),
        ]);
        $unknown = $this->inProgressPayment('pay-404', 'unknown');
        $known = $this->inProgressPayment('pay-404-ok');

        $updated = app(PaymentService::class)->reconcilePending();

        $this->assertEquals(1, $updated);
        $this->assertEquals(PaymentStatus::InProgress->value, $unknown->fresh()->status);
        $this->assertEquals(PaymentStatus::Paid->value, $known->fresh()->status);
    }

    // ── 8. Sans gateway_reference ─────────────────────────────────────────

    /** @test */
    public function payment_without_gateway_reference_is_never_verified_and_fails_after_two_minutes(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::VERIFY => $this->verifyResponse('Successful')]);
        $payment = $this->inProgressPayment('pay-orphan', null);

        $service = app(PaymentService::class);

        $this->assertEquals(0, $service->reconcilePending());
        $this->assertEquals(PaymentStatus::InProgress->value, $payment->fresh()->status);

        $payment->forceFill(['created_at' => now()->subMinutes(3)])->save();

        $this->assertEquals(1, $service->reconcilePending());
        $this->assertEquals(PaymentStatus::Failed->value, $payment->fresh()->status);
        $this->assertStringContainsString('jamais initiée', $payment->fresh()->metadata['error']);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/verify/'));
    }

    // ── 9. Expiration ─────────────────────────────────────────────────────

    /** @test */
    public function payment_expires_after_the_configured_timeout_with_a_last_verification(): void
    {
        $this->gateway(['timeout_minutes' => 3]);
        Http::fake([self::LOGIN => $this->loginResponse(), self::VERIFY => $this->verifyResponse('Payment in progress')]);
        $payment = $this->inProgressPayment('pay-expire');
        $service = app(PaymentService::class);

        $payment->forceFill(['created_at' => now()->subMinutes(2)])->save();
        $service->reconcilePending();
        $this->assertEquals(PaymentStatus::InProgress->value, $payment->fresh()->status);

        $payment->forceFill(['created_at' => now()->subMinutes(4)])->save();
        $service->reconcilePending();

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals(PaymentService::STATUS_EXPIRED, $payment->gateway_status);
        $this->assertEquals(2, $payment->checks);
    }

    /** @test */
    public function expired_payment_confirmed_later_by_payme_is_recovered(): void
    {
        $this->gateway(['timeout_minutes' => 3]);
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::VERIFY => Http::sequence()
                ->push(['success' => true, 'data' => ['status' => 'Payment in progress']])
                ->push(['success' => true, 'data' => ['status' => 'Successful', 'payment_provider_slug' => 'mtn']]),
        ]);
        $payment = $this->inProgressPayment('pay-late');
        $payment->forceFill(['created_at' => now()->subMinutes(10)])->save();
        $service = app(PaymentService::class);

        $service->reconcilePending();
        $this->assertEquals(PaymentStatus::Failed->value, $payment->fresh()->status);

        $service->reconcilePending();

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Paid->value, $payment->status);
        $this->assertEquals('Successful', $payment->gateway_status);
        $this->assertNotNull($payment->subscription_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.recovered', 'auditable_id' => $payment->id]);
    }

    /** @test */
    public function expired_payment_is_no_longer_rechecked_after_the_recovery_window(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::VERIFY => $this->verifyResponse('Successful')]);
        $this->inProgressPayment('pay-old', self::REF, [
            'status' => PaymentStatus::Failed->value,
            'gateway_status' => PaymentService::STATUS_EXPIRED,
            'metadata' => ['failed_at' => now()->subHours(2)->toIso8601String()],
        ]);

        $this->assertEquals(0, app(PaymentService::class)->reconcilePending());
        Http::assertNothingSent();
    }

    // ── 10. Annulation ────────────────────────────────────────────────────

    /** @test */
    public function cancelled_request_then_successful_verification_is_paid(): void
    {
        $this->gateway();
        Http::fake([
            self::LOGIN => $this->loginResponse(),
            self::VERIFY => Http::sequence()
                ->push(['success' => true, 'data' => ['status' => 'Payment in progress']])
                ->push(['success' => true, 'data' => ['status' => 'Successful']]),
        ]);
        $payment = $this->inProgressPayment('pay-cancel');
        $service = app(PaymentService::class);

        $payment = $service->cancel($payment);

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals(PaymentService::STATUS_CANCELLED, $payment->gateway_status);
        $this->assertStringContainsString('annulée', $payment->metadata['error']);

        $service->reconcilePending();

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Paid->value, $payment->status);
        $this->assertEquals(1, $payment->tenant->subscriptions()->where('status', 'active')->count());
    }

    /** @test */
    public function cancel_endpoint_is_limited_to_the_owning_tenant(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse(), self::VERIFY => $this->verifyResponse('Payment in progress')]);

        $admin = User::factory()->create(['tenant_id' => $this->defaultTenantId]);
        $admin->assignRole('administrateur');

        $own = Payment::create([
            'tenant_id' => $this->defaultTenantId,
            'amount' => 1000,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => self::REF,
        ]);
        $other = $this->inProgressPayment('pay-other', self::REF.'-other');

        $this->actingAs($admin)->postJson("/api/billing/payments/{$other->id}/cancel")->assertStatus(404);

        $this->actingAs($admin)->postJson("/api/billing/payments/{$own->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.payment.status', PaymentStatus::Failed->value)
            ->assertJsonPath('data.payment.gateway_status', PaymentService::STATUS_CANCELLED);

        $this->actingAs($admin)->postJson("/api/billing/payments/{$own->id}/cancel")->assertStatus(422);
    }

    // ── 11. Tenant démo ───────────────────────────────────────────────────

    /** @test */
    public function checkout_is_refused_on_the_demo_tenant(): void
    {
        $this->gateway();
        Http::fake();

        $demo = Tenant::where('slug', 'demo')->firstOrFail();

        $this->expectException(\RuntimeException::class);

        app(PaymentService::class)->checkout($demo, $this->pack(), '237690000009');
    }

    // ── 12. Configuration ─────────────────────────────────────────────────

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    /** @test */
    public function gateway_configuration_rejects_invalid_values(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->putJson('/api/admin/payment-gateway', ['user_name' => 'u', 'endpoint' => 'https://console-v2.mamonipay.me/docs/api-json'])
            ->assertStatus(422)->assertJsonStructure(['data' => ['endpoint']]);

        $this->actingAs($admin)->putJson('/api/admin/payment-gateway', ['user_name' => 'u', 'endpoint' => 'https://console-v2.mamonipay.me/api/v1'])
            ->assertStatus(422)->assertJsonStructure(['data' => ['endpoint']]);

        $this->actingAs($admin)->putJson('/api/admin/payment-gateway', ['user_name' => 'u', 'client_fees_rate' => 150])
            ->assertStatus(422)->assertJsonStructure(['data' => ['client_fees_rate']]);

        $this->actingAs($admin)->putJson('/api/admin/payment-gateway', ['user_name' => 'u', 'timeout_minutes' => 1])
            ->assertStatus(422)->assertJsonStructure(['data' => ['timeout_minutes']]);

        $this->actingAs($admin)->putJson('/api/admin/payment-gateway', [
            'user_name' => '237690000000',
            'password' => 'secret',
            'endpoint' => 'https://console-v2.mamonipay.me/api/',
            'client_fees_rate' => null,
            'timeout_minutes' => 10,
            'is_active' => true,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.gateway.endpoint', 'https://console-v2.mamonipay.me/api')
            ->assertJsonPath('data.gateway.timeout_minutes', 10)
            ->assertJsonPath('data.gateway.client_fees_rate', null)
            ->assertJsonMissingPath('data.gateway.password')
            ->assertJsonMissingPath('data.gateway.mode');
    }

    /** @test */
    public function connection_test_reports_the_called_url_on_failure(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => Http::response(['message' => 'Not Found'], 404)]);

        $this->actingAs($this->superAdmin())->postJson('/api/admin/payment-gateway/test')
            ->assertStatus(200)
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.status', 404)
            ->assertJsonPath('data.url', self::LOGIN);
    }

    /** @test */
    public function connection_test_succeeds_with_valid_credentials(): void
    {
        $this->gateway();
        Http::fake([self::LOGIN => $this->loginResponse()]);

        $this->actingAs($this->superAdmin())->postJson('/api/admin/payment-gateway/test')
            ->assertStatus(200)
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.status', 200);
    }

    /** @test */
    public function status_mapping_normalizes_spaces_and_case(): void
    {
        $this->assertEquals('success', PaymeClient::mapStatus('Successful'));
        $this->assertEquals('pending', PaymeClient::mapStatus('Payment in progress'));
        $this->assertEquals('pending', PaymeClient::mapStatus('Awaiting validation'));
        $this->assertEquals('failed', PaymeClient::mapStatus('cancelled'));
        $this->assertEquals('failed', PaymeClient::mapStatus('EXPIRED'));
        $this->assertEquals('pending', PaymeClient::mapStatus(null));
    }

    /** @test */
    public function illegal_transition_is_a_no_op(): void
    {
        $tenant = $this->tenant('pay-state');

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 1000,
            'currency' => 'XAF',
            'status' => PaymentStatus::Paid->value,
        ]);

        $service = app(PaymentService::class);
        $result = $service->transition($payment, PaymentStatus::Pending);

        $this->assertEquals(PaymentStatus::Paid->value, $result->status);

        $result = $service->transition($payment, PaymentStatus::Refunded);

        $this->assertEquals(PaymentStatus::Refunded->value, $result->status);
    }
}
