<?php

namespace App\Services;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cycle de vie des paiements PayMe (MamoniPay).
 *
 * Idempotence (cf. §7 du plan) :
 * - DB : `gateway_reference`, `external_reference`, `idempotency_key` UNIQUE.
 * - Service : machine à états `PaymentStatus` (transition illégale = no-op).
 * - API : header `Idempotency-Key` sur le checkout (rejeu → paiement existant).
 */
class PaymentService
{
    public function __construct(
        protected PaymeClient $client,
        protected TenantSubscriptionsService $subscriptions,
    ) {}

    /**
     * Initie un paiement d'abonnement pour le tenant.
     */
    public function checkout(
        Tenant $tenant,
        Pack $pack,
        string $phone,
        ?string $billingPeriod = null,
        ?string $idempotencyKey = null,
    ): Payment {
        // if ($tenant->is_demo) {
        //     throw new \RuntimeException('Les paiements réels sont désactivés sur le tenant démo.');
        // }

        $gateway = PaymentGateway::active();

        if (! $gateway) {
            throw new \RuntimeException('Aucune passerelle de paiement active.');
        }

        $period = $billingPeriod ?? $pack->billing_period;

        if ($idempotencyKey) {
            $existing = Payment::query()
                ->where('tenant_id', $tenant->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'amount' => $pack->price,
            'currency' => $pack->currency ?: 'XAF',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => $idempotencyKey,
            'external_reference' => (string) Str::uuid(),
            'payment_method' => 'mobile_money',
            'metadata' => [
                'pack_id' => $pack->id,
                'billing_period' => $period,
                'phone' => $phone,
            ],
        ]);

        AuditService::record('payment.created', $payment, [], [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'pack_id' => $pack->id,
        ]);

        try {
            $result = $this->client->initiatePayment($gateway, [
                'amount' => $pack->price,
                'phone' => $this->client->normalizePhone($phone),
                'external_reference' => $payment->external_reference,
                'client_fees_rate' => $this->clientFeesRate($gateway),
            ]);
        } catch (\Throwable $e) {
            Log::error('PayMe : exception lors de l\'initiation du paiement.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => PaymentStatus::Failed->value,
                'metadata' => array_merge($payment->metadata ?? [], ['error' => $e->getMessage()]),
            ]);

            AuditService::record('payment.failed', $payment, ['status' => PaymentStatus::Pending->value], ['status' => PaymentStatus::Failed->value, 'error' => $e->getMessage()]);

            return $payment->fresh();
        }

        if (! $result['success']) {
            Log::error('PayMe : initiation du paiement refusée.', [
                'payment_id' => $payment->id,
                'error' => $result['error'] ?? null,
            ]);

            $payment->update([
                'status' => PaymentStatus::Failed->value,
                'metadata' => array_merge($payment->metadata ?? [], ['error' => $result['error'] ?? null]),
            ]);

            AuditService::record('payment.failed', $payment, ['status' => PaymentStatus::Pending->value], ['status' => PaymentStatus::Failed->value, 'error' => $result['error'] ?? null]);

            return $payment->fresh();
        }

        $payment->update([
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $result['gateway_reference'] ?? null,
        ]);

        AuditService::record('payment.in_progress', $payment, ['status' => PaymentStatus::Pending->value], ['status' => PaymentStatus::InProgress->value, 'gateway_reference' => $result['gateway_reference'] ?? null]);

        return $payment->fresh();
    }

    /**
     * Applique une transition d'état (machine à états) ; no-op si illégale.
     */
    public function transition(Payment $payment, PaymentStatus $next): Payment
    {
        $current = $payment->statusEnum();

        if (! $current->canTransitionTo($next)) {
            return $payment;
        }

        if (in_array($next, [PaymentStatus::Paid, PaymentStatus::Deposited], true)) {
            return $this->markPaid($payment, $next);
        }

        $payment->update(['status' => $next->value]);

        AuditService::record("payment.{$next->value}", $payment, ['status' => $current->value], ['status' => $next->value]);

        return $payment->fresh();
    }

    /**
     * Réconcilie les paiements en attente (polling).
     *
     * Au-delà du délai de polling configuré (`payme.polling_timeout`), un
     * paiement toujours en attente est marqué « à vérifier manuellement » au
     * lieu d'être réinterrogé (cf. MAMONIPAY_API.md).
     *
     * @return int Nombre de paiements mis à jour.
     */
    public function reconcilePending(): int
    {
        $gateway = PaymentGateway::active();

        if (! $gateway) {
            return 0;
        }

        $count = 0;
        $staleBefore = now()->subMinutes((int) config('payme.polling_timeout', 15));

        $pending = Payment::query()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::InProgress->value])
            ->whereNotNull('gateway_reference')
            ->get();

        foreach ($pending as $payment) {
            // Paiement périmé : ne plus interroger, placer en révision manuelle.
            if ($payment->created_at && $payment->created_at->lte($staleBefore)) {
                $before = $payment->status;
                $this->transition($payment, PaymentStatus::NeedsReview);

                if ($payment->fresh()->status !== $before) {
                    $count++;
                }

                continue;
            }

            $result = $this->client->paymentStatus($gateway, $payment->gateway_reference);

            if (! $result['success']) {
                continue;
            }

            $before = $payment->status;
            $this->applyPaymeStatus($payment, $result['status'] ?? null);

            if ($payment->fresh()->status !== $before) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Mappe un statut PayMe vers le statut interne et applique la transition.
     */
    private function applyPaymeStatus(Payment $payment, ?string $paymeStatus): Payment
    {
        $internal = match (strtoupper((string) $paymeStatus)) {
            'SUCCESSFUL' => PaymentStatus::Paid,
            'FAILED' => PaymentStatus::Failed,
            'PAYMENT_IN_PROGRESS' => PaymentStatus::InProgress,
            default => null,
        };

        if (! $internal) {
            Log::warning('PayMe : statut inconnu, transaction non conclue.', [
                'payment_id' => $payment->id,
                'gateway_reference' => $payment->gateway_reference,
                'status' => $paymeStatus,
            ]);

            return $payment;
        }

        return $this->transition($payment, $internal);
    }

    /**
     * Marque le paiement comme payé/déposé et active l'abonnement.
     */
    private function markPaid(Payment $payment, PaymentStatus $status): Payment
    {
        if ($payment->status === $status->value) {
            return $payment;
        }

        $oldStatus = $payment->status;

        DB::transaction(function () use ($payment, $status) {
            $payment->update([
                'status' => $status->value,
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $this->activateSubscription($payment);
        });

        AuditService::record("payment.{$status->value}", $payment, ['status' => $oldStatus], [
            'status' => $status->value,
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ]);

        return $payment->fresh();
    }

    /**
     * Active l'abonnement associé au paiement réussi.
     */
    private function activateSubscription(Payment $payment): void
    {
        $packId = $payment->metadata['pack_id'] ?? null;

        if (! $packId) {
            return;
        }

        $pack = Pack::query()->find($packId);

        if (! $pack || ! $payment->tenant) {
            return;
        }

        $period = $payment->metadata['billing_period'] ?? $pack->billing_period;

        $subscription = $this->subscriptions->subscribe($payment->tenant, $pack, $period);

        $payment->update(['subscription_id' => $subscription->id]);
    }

    /**
     * Convertit le taux de frais client (stocké en fraction 0–1, ex. 0.01 = 1 %)
     * en pourcentage entier (0–100) attendu par PayMe. Défaut : 100.
     */
    private function clientFeesRate(PaymentGateway $gateway): int
    {
        $rate = (float) $gateway->client_fees_rate;

        return $rate > 0 ? (int) round($rate * 100) : 100;
    }
}
