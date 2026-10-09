<?php

namespace App\Services;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentWebhook;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie des paiements PayMe (MamoniPay).
 *
 * Idempotence (cf. §7 du plan) :
 * - DB : `gateway_reference`, `external_reference`, `idempotency_key` UNIQUE.
 * - Service : machine à états `PaymentStatus` (transition illégale = no-op) +
 *   `firstOrCreate` sur le hash de webhook.
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
        if ($tenant->is_demo) {
            throw new \RuntimeException('Les paiements réels sont désactivés sur le tenant démo.');
        }

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
            'external_reference' => 'CAMPUS-'.strtoupper(uniqid()),
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
            $token = $this->client->token($gateway);
            $result = $this->client->initiatePayment($gateway, $token, [
                'amount' => $pack->price,
                'currency' => $payment->currency,
                'phone_number' => $phone,
                'external_reference' => $payment->external_reference,
                'pay_type_id' => $gateway->pay_type_id,
                'app_id' => $gateway->app_id,
            ]);
        } catch (\Throwable $e) {
            $payment->update([
                'status' => PaymentStatus::Failed->value,
                'metadata' => array_merge($payment->metadata ?? [], ['error' => $e->getMessage()]),
            ]);

            AuditService::record('payment.failed', $payment, ['status' => PaymentStatus::Pending->value], ['status' => PaymentStatus::Failed->value, 'error' => $e->getMessage()]);

            return $payment->fresh();
        }

        if (! $result['success']) {
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
     * Traite un callback PayMe (idempotent par hash du payload).
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data): ?Payment
    {
        $hash = hash('sha256', json_encode($data));

        $webhook = PaymentWebhook::query()->firstOrCreate(
            ['hash' => $hash],
            ['payload' => $data, 'received_at' => now()]
        );

        if (! $webhook->wasRecentlyCreated) {
            return null; // déjà traité
        }

        $gatewayReference = $data['gateway_reference']
            ?? $data['reference']
            ?? $data['transaction_id']
            ?? null;

        if (! $gatewayReference) {
            return null;
        }

        $payment = Payment::query()->where('gateway_reference', $gatewayReference)->first();

        if (! $payment) {
            return null;
        }

        return $this->applyPaymeStatus($payment, $data['status'] ?? null);
    }

    /**
     * Réconcilie les paiements en attente (polling de secours).
     *
     * @return int Nombre de paiements mis à jour.
     */
    public function reconcilePending(): int
    {
        $gateway = PaymentGateway::active();

        if (! $gateway) {
            return 0;
        }

        $token = $this->client->token($gateway);
        $count = 0;

        $pending = Payment::query()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::InProgress->value])
            ->whereNotNull('gateway_reference')
            ->get();

        foreach ($pending as $payment) {
            $result = $this->client->paymentStatus($gateway, $token, $payment->gateway_reference);

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
            'SUCCESSFUL', 'PAID' => PaymentStatus::Paid,
            'DEPOSIT' => PaymentStatus::Deposited,
            'FAILED' => PaymentStatus::Failed,
            'PAYMENT_IN_PROGRESS' => PaymentStatus::InProgress,
            'NOT_PAID' => PaymentStatus::Pending,
            default => null,
        };

        if (! $internal) {
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
}
