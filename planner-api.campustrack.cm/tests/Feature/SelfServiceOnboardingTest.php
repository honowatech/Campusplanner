<?php

namespace Tests\Feature;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Models\User;
use App\Support\FeatureRegistry;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Parcours libre-service complet : une école s'inscrit, choisit une formule,
 * paie et utilise l'application sans aucune action du super-admin.
 */
class SelfServiceOnboardingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();

        // Seule configuration plateforme, faite une fois pour toutes.
        PaymentGateway::create([
            'provider' => 'payme',
            'user_name' => '237600000000',
            'password' => 'secret',
            'is_active' => true,
        ]);
    }

    private function signupPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Institut Universitaire du Golfe',
            'admin_name' => 'Awa Nkoulou',
            'admin_email' => 'direction@iug.cm',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'phone' => '699000000',
        ], $overrides);
    }

    /** @test */
    public function packs_are_public_for_the_signup_page(): void
    {
        $this->getJson('/api/packs')
            ->assertOk()
            ->assertJsonCount(6, 'data.packs');
    }

    /** @test */
    public function a_school_signs_up_pays_and_gets_its_pack_without_super_admin(): void
    {
        $pack = Pack::where('slug', 'standard-monthly')->firstOrFail();

        Http::fake([
            '*/v1/auth/login' => Http::response(['success' => true, 'data' => ['access_token' => 'jwt']], 200),
            '*/v1/payments/init' => Http::response(['success' => true, 'data' => ['gateway_reference' => 'gw-self', 'status' => 'Initiated']], 200),
            '*/v1/payments/verify/*' => Http::sequence()
                ->push(['success' => true, 'data' => ['status' => 'Payment in progress']], 200)
                ->push(['success' => true, 'data' => ['status' => 'Successful', 'payment_provider_slug' => 'mtn']], 200),
        ]);

        // 1. Inscription : l'école est créée et l'admin directement connecté.
        $this->asSpaClient()
            ->postJson('/api/onboarding', $this->signupPayload(['pack_id' => $pack->id]))
            ->assertCreated()
            ->assertJsonPath('data.intended_pack_id', $pack->id)
            ->assertJsonPath('data.user.email', 'direction@iug.cm')
            ->assertJsonPath('data.tenant.slug', 'institut-universitaire-du-golfe');

        $tenant = Tenant::where('slug', 'institut-universitaire-du-golfe')->firstOrFail();
        $this->assertSame('699000000', $tenant->billing_phone);
        $this->assertAuthenticated('web');

        // 2. Il utilise l'application tout de suite (essai gratuit, socle).
        $this->getJson('/api/auth-user')
            ->assertOk()
            ->assertJsonPath('data.subscription.status', 'trial');
        $this->getJson('/api/rooms')->assertOk();

        // 3. Il paie la formule choisie.
        $paymentId = $this->postJson('/api/billing/checkout', [
            'pack_id' => $pack->id,
            'phone' => '699000000',
            'billing_period' => 'monthly',
        ], ['Idempotency-Key' => 'signup-1'])
            ->assertCreated()
            ->assertJsonPath('data.payment.status', PaymentStatus::InProgress->value)
            ->json('data.payment.id');

        // 4. La page suit la validation sur le téléphone, puis active le pack.
        $this->getJson("/api/billing/payments/{$paymentId}")
            ->assertOk()
            ->assertJsonPath('data.payment.status', PaymentStatus::InProgress->value);

        $this->getJson("/api/billing/payments/{$paymentId}")
            ->assertOk()
            ->assertJsonPath('data.payment.status', PaymentStatus::Paid->value);

        $this->getJson('/api/billing/subscription')
            ->assertOk()
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.subscription.pack.id', $pack->id);

        $this->assertTrue(FeatureRegistry::has($tenant->fresh(), 'ai-scheduling'));
    }

    /** @test */
    public function slug_is_generated_and_made_unique(): void
    {
        $this->postJson('/api/onboarding', $this->signupPayload())->assertCreated();

        $this->forgetResolvedGuards();
        $this->postJson('/api/onboarding', $this->signupPayload(['admin_email' => 'autre@iug.cm']))
            ->assertCreated()
            ->assertJsonPath('data.tenant.slug', 'institut-universitaire-du-golfe-2');
    }

    /** @test */
    public function signup_requires_password_confirmation_and_unique_email(): void
    {
        $this->postJson('/api/onboarding', $this->signupPayload(['admin_password_confirmation' => 'different']))
            ->assertStatus(422);

        User::factory()->create(['email' => 'taken@iug.cm']);

        $this->postJson('/api/onboarding', $this->signupPayload(['admin_email' => 'taken@iug.cm']))
            ->assertStatus(422);
    }

    /** @test */
    public function a_school_cannot_read_or_cancel_another_schools_payment(): void
    {
        $other = Tenant::create(['name' => 'Autre', 'slug' => 'autre']);
        $payment = Payment::create([
            'tenant_id' => $other->id,
            'amount' => 15000,
            'currency' => 'XAF',
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => 'gw-other',
        ]);

        $this->asSpaClient()->postJson('/api/onboarding', $this->signupPayload())->assertCreated();

        $this->getJson("/api/billing/payments/{$payment->id}")->assertNotFound();
        $this->postJson("/api/billing/payments/{$payment->id}/cancel")->assertNotFound();
    }

    /** @test */
    public function checkout_errors_are_returned_as_422_with_a_message(): void
    {
        PaymentGateway::query()->update(['is_active' => false]);
        $pack = Pack::where('slug', 'starter-monthly')->firstOrFail();

        $this->asSpaClient()->postJson('/api/onboarding', $this->signupPayload())->assertCreated();

        $this->postJson('/api/billing/checkout', ['pack_id' => $pack->id, 'phone' => '699000000'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('admin.payments.gateway_unavailable'));
    }
}
