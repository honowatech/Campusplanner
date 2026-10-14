<?php

namespace Tests\Feature;

use App\Models\Pack;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\TenantSubscriptionsService;
use App\Support\FeatureRegistry;
use Tests\TestCase;

class SubscriptionTest extends TestCase
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
    public function start_trial_creates_a_trial_without_pack(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'trial-tenant'], ['name' => 'Trial Tenant']);

        $trial = $this->subscriptions->startTrial($tenant, 14);

        $this->assertEquals(TenantSubscription::STATUS_TRIAL, $trial->status);
        $this->assertNull($trial->pack_id);
        $this->assertNotNull($trial->trial_ends_at);
    }

    /** @test */
    public function subscribe_creates_an_active_subscription_with_an_end_date(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'sub-tenant'], ['name' => 'Sub Tenant']);
        $pack = Pack::where('slug', 'standard-monthly')->firstOrFail();

        $subscription = $this->subscriptions->subscribe($tenant, $pack);

        $this->assertEquals(TenantSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertEquals($pack->id, $subscription->pack_id);
        $this->assertEquals(TenantSubscription::PERIOD_MONTHLY, $subscription->billing_period);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    /** @test */
    public function subscribing_cancels_the_previous_trial(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'sub-cancel'], ['name' => 'Sub Cancel']);
        $this->subscriptions->startTrial($tenant);

        $pack = Pack::where('slug', 'premium-monthly')->firstOrFail();
        $this->subscriptions->subscribe($tenant, $pack);

        $this->assertEquals(0, $tenant->subscriptions()->where('status', TenantSubscription::STATUS_TRIAL)->count());
        $this->assertEquals(1, $tenant->subscriptions()->where('status', TenantSubscription::STATUS_ACTIVE)->count());
        $this->assertEquals(1, $tenant->subscriptions()->where('status', TenantSubscription::STATUS_CANCELED)->count());
    }

    /** @test */
    public function mark_expired_expires_past_subscriptions(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'expired-tenant'], ['name' => 'Expired Tenant']);
        $pack = Pack::where('slug', 'starter-monthly')->firstOrFail();

        $subscription = $this->subscriptions->subscribe($tenant, $pack);
        $subscription->update(['ends_at' => now()->subDay()]);

        $count = $this->subscriptions->markExpired();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertEquals(TenantSubscription::STATUS_EXPIRED, $subscription->fresh()->status);
    }
}
