<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\FeatureRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie des abonnements d'un tenant.
 *
 * - Essai gratuit (`startTrial`).
 * - Souscription manuelle (`subscribe`) : un pack pour une période mensuelle
 *   ou annuelle, sans renouvellement automatique.
 * - Renouvellement manuel (`renew`) : prolonge l'abonnement courant.
 * - Expiration (`markExpired`) : passe à `expired` les abonnements échus.
 *
 * Chaque transition invalide le cache des fonctionnalités du tenant.
 */
class TenantSubscriptionsService
{
    /**
     * Démarre (ou redémarre) un essai gratuit pour le tenant.
     */
    public function startTrial(Tenant $tenant, int $days = 14): TenantSubscription
    {
        return DB::transaction(function () use ($tenant, $days) {
            $this->cancelCurrent($tenant);

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'pack_id' => null,
                'status' => TenantSubscription::STATUS_TRIAL,
                'billing_period' => TenantSubscription::PERIOD_MONTHLY,
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays($days),
                'auto_renew' => false,
            ]);

            FeatureRegistry::flushCache($tenant);

            AuditService::record('subscription.trial_started', $subscription, [], [
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            ]);

            return $subscription;
        });
    }

    /**
     * Souscrit le tenant à un pack (renouvellement MANUEL).
     */
    public function subscribe(Tenant $tenant, Pack $pack, ?string $billingPeriod = null, ?Carbon $startsAt = null): TenantSubscription
    {
        $period = $billingPeriod ?? $pack->billing_period;
        $start = $startsAt ?? now();

        return DB::transaction(function () use ($tenant, $pack, $period, $start) {
            $this->cancelCurrent($tenant);

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'pack_id' => $pack->id,
                'status' => TenantSubscription::STATUS_ACTIVE,
                'billing_period' => $period,
                'starts_at' => $start,
                'ends_at' => $this->periodEnd($period, $start),
                'auto_renew' => false,
            ]);

            FeatureRegistry::flushCache($tenant);

            AuditService::record('subscription.activated', $subscription, [], [
                'pack_id' => $pack->id,
                'pack_name' => $pack->name,
                'billing_period' => $period,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ]);

            return $subscription;
        });
    }

    /**
     * Renouvelle manuellement : prolonge l'abonnement courant s'il est encore
     * valide, sinon en démarre un nouveau.
     */
    public function renew(Tenant $tenant, Pack $pack, ?string $billingPeriod = null): TenantSubscription
    {
        $period = $billingPeriod ?? $pack->billing_period;

        return DB::transaction(function () use ($tenant, $pack, $period) {
            $current = $this->currentFor($tenant);

            $base = ($current && $current->ends_at && $current->ends_at->isFuture())
                ? $current->ends_at
                : now();

            $this->cancelCurrent($tenant);

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'pack_id' => $pack->id,
                'status' => TenantSubscription::STATUS_ACTIVE,
                'billing_period' => $period,
                'starts_at' => $base,
                'ends_at' => $this->periodEnd($period, $base),
                'auto_renew' => false,
            ]);

            FeatureRegistry::flushCache($tenant);

            AuditService::record('subscription.renewed', $subscription, [], [
                'pack_id' => $pack->id,
                'billing_period' => $period,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ]);

            return $subscription;
        });
    }

    /**
     * Abonnement courant du tenant (essai ou actif), le plus récent.
     */
    public function currentFor(Tenant $tenant): ?TenantSubscription
    {
        return $tenant->subscriptions()
            ->whereIn('status', [TenantSubscription::STATUS_TRIAL, TenantSubscription::STATUS_ACTIVE])
            ->latest('starts_at')
            ->first();
    }

    /**
     * Passe à `expired` les abonnements et essais échus.
     * À exécuter via une commande planifiée (contexte global).
     */
    public function markExpired(): int
    {
        $count = 0;

        $expired = TenantSubscription::query()
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->update(['status' => TenantSubscription::STATUS_EXPIRED]);
            FeatureRegistry::flushCache($subscription->tenant_id);
            AuditService::record('subscription.expired', $subscription, ['status' => TenantSubscription::STATUS_ACTIVE], ['status' => TenantSubscription::STATUS_EXPIRED]);
            $count++;
        }

        $trials = TenantSubscription::query()
            ->where('status', TenantSubscription::STATUS_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($trials as $subscription) {
            $subscription->update(['status' => TenantSubscription::STATUS_EXPIRED]);
            FeatureRegistry::flushCache($subscription->tenant_id);
            AuditService::record('subscription.expired', $subscription, ['status' => TenantSubscription::STATUS_TRIAL], ['status' => TenantSubscription::STATUS_EXPIRED]);
            $count++;
        }

        return $count;
    }

    /**
     * Annule l'abonnement/essai courant du tenant.
     */
    public function cancel(Tenant $tenant): void
    {
        $this->cancelCurrent($tenant);
        FeatureRegistry::flushCache($tenant);

        AuditService::record('subscription.canceled', null, [], ['tenant_id' => $tenant->id]);
    }

    /**
     * Résumé sérialisable de l'abonnement courant (pour /auth-user, billing UI).
     *
     * @return array<string, mixed>|null
     */
    public function currentSummary(Tenant $tenant): ?array
    {
        $subscription = $this->currentFor($tenant);

        if (! $subscription) {
            return null;
        }

        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'billing_period' => $subscription->billing_period,
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'auto_renew' => (bool) $subscription->auto_renew,
            'pack' => $subscription->pack ? [
                'id' => $subscription->pack->id,
                'name' => $subscription->pack->name,
                'tier' => $subscription->pack->tier,
                'slug' => $subscription->pack->slug,
                'price' => (float) $subscription->pack->price,
                'currency' => $subscription->pack->currency,
            ] : null,
        ];
    }

    /**
     * Annule (statut `canceled`) l'essai/abonnement courant du tenant.
     */
    private function cancelCurrent(Tenant $tenant): void
    {
        $tenant->subscriptions()
            ->whereIn('status', [TenantSubscription::STATUS_TRIAL, TenantSubscription::STATUS_ACTIVE])
            ->update(['status' => TenantSubscription::STATUS_CANCELED]);
    }

    /**
     * Date de fin d'une période de facturation.
     */
    private function periodEnd(string $period, Carbon $from): Carbon
    {
        return $period === TenantSubscription::PERIOD_ANNUAL
            ? $from->copy()->addYear()
            : $from->copy()->addMonth();
    }
}
