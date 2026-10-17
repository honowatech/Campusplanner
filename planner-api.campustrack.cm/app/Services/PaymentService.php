<?php

namespace App\Services;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cycle de vie des paiements PayMe (MamoniPay), en libre-service.
 *
 * PayMe n'envoie aucune notification : la confirmation repose sur la
 * vérification active (`verify`), déclenchée par la page de paiement de l'école
 * (polling) et par la planification (`reconcilePending`, chaque minute). Un
 * paiement confirmé active l'abonnement sans intervention du super-admin.
 *
 * Idempotence :
 * - DB : `gateway_reference`, `external_reference`, `idempotency_key` UNIQUE.
 * - Service : machine à états `PaymentStatus` (transition illégale = no-op).
 * - API : en-tête `Idempotency-Key` sur le checkout (rejeu → paiement existant).
 */
class PaymentService
{
    /** Délai (minutes) au-delà duquel une demande sans référence PayMe est abandonnée. */
    private const UNREFERENCED_GRACE_MINUTES = 2;

    public function __construct(
        protected PaymeClient $client,
        protected TenantSubscriptionsService $subscriptions,
    ) {}

    /**
     * Initie un paiement d'abonnement pour le tenant.
     *
     * @throws \RuntimeException Tenant démo, passerelle absente ou paiement déjà en attente.
     */
    public function checkout(
        Tenant $tenant,
        Pack $pack,
        string $phone,
        ?string $billingPeriod = null,
        ?string $idempotencyKey = null,
    ): Payment {
        if ($tenant->is_demo) {
            throw new \RuntimeException(__('admin.payments.demo_disabled'));
        }

        if ($idempotencyKey) {
            $existing = Payment::query()
                ->where('tenant_id', $tenant->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $gateway = PaymentGateway::active();

        if (! $gateway) {
            throw new \RuntimeException(__('admin.payments.gateway_unavailable'));
        }

        $awaiting = Payment::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::InProgress->value])
            ->exists();

        if ($awaiting) {
            throw new \RuntimeException(__('admin.payments.already_pending'));
        }

        $period = $billingPeriod ?? $pack->billing_period;
        $phoneNumber = $this->client->normalizePhone($phone);

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
                'pack_name' => $pack->name,
                'billing_period' => $period,
                'phone' => $phoneNumber,
                'timeout_minutes' => $gateway->timeoutMinutes(),
            ],
        ]);

        AuditService::record('payment.created', $payment, [], [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'pack_id' => $pack->id,
        ]);

        try {
            $result = $this->client->initiatePayment($gateway, [
                'amount' => (int) round($pack->price),
                'phone_number' => $phoneNumber,
                'external_reference' => $payment->external_reference,
                'client_name' => $tenant->name,
                'client_fees_rate' => $gateway->client_fees_rate,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayMe : exception lors de l\'initiation du paiement.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return $this->fail($payment, $e->getMessage());
        }

        if ($result['state'] === 'failed') {
            return $this->fail($payment, $result['message'] ?? null, $result['gateway_status'] ?? null);
        }

        if (blank($result['gateway_reference'] ?? null)) {
            return $this->fail($payment, 'PayMe n\'a pas renvoyé de référence de transaction.');
        }

        $payment->update([
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $result['gateway_reference'],
            'gateway_status' => $result['gateway_status'] ?? null,
            'provider' => $result['provider'] ?? null,
        ]);

        AuditService::record('payment.in_progress', $payment, ['status' => PaymentStatus::Pending->value], [
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $result['gateway_reference'],
        ]);

        // Certains opérateurs confirment immédiatement.
        if ($result['state'] === 'success') {
            return $this->markPaid($payment->fresh(), PaymentStatus::Paid, $result);
        }

        return $payment->fresh();
    }

    /**
     * Vérifie activement un paiement auprès de PayMe et applique le résultat.
     *
     * Appelée par la page de paiement (polling) et par `reconcilePending`.
     * Une demande en attente au-delà du délai configuré expire (`EXPIRED`) ;
     * une demande jamais référencée chez PayMe est abandonnée après 2 minutes.
     */
    public function verify(Payment $payment): Payment
    {
        $status = $payment->statusEnum();

        if ($status === PaymentStatus::Failed && $this->isRecoverable($payment)) {
            return $this->recover($payment);
        }

        if (! in_array($status, [PaymentStatus::Pending, PaymentStatus::InProgress], true)) {
            return $payment;
        }

        if (blank($payment->gateway_reference)) {
            if ($payment->created_at?->lte(now()->subMinutes(self::UNREFERENCED_GRACE_MINUTES))) {
                return $this->fail($payment, 'Demande jamais initiée chez PayMe.');
            }

            return $payment;
        }

        $gateway = PaymentGateway::active();

        if (! $gateway) {
            return $payment;
        }

        try {
            $result = $this->client->paymentStatus($gateway, $payment->gateway_reference);
        } catch (\Throwable $e) {
            // 404 TXN-002, 5xx, réseau : issue incertaine, on réessaiera.
            Log::warning('PayMe : vérification impossible.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            $result = null;
        }

        $payment->update([
            'checked_at' => now(),
            'checks' => $payment->checks + 1,
        ]);

        if ($result) {
            $payment = $this->applyResult($payment, $result);

            if ($payment->statusEnum() !== PaymentStatus::InProgress && $payment->statusEnum() !== PaymentStatus::Pending) {
                return $payment;
            }
        }

        if ($this->isExpired($payment, $gateway)) {
            return $this->fail($payment, 'Délai de validation dépassé.', 'EXPIRED');
        }

        return $payment;
    }

    /**
     * Le client renonce à attendre : dernière vérification, puis `CANCELLED`.
     * La demande reste revérifiée pendant `payme.recovery_minutes`.
     */
    public function cancel(Payment $payment): Payment
    {
        $payment = $this->verify($payment);

        if (! in_array($payment->statusEnum(), [PaymentStatus::Pending, PaymentStatus::InProgress], true)) {
            return $payment;
        }

        return $this->fail($payment, 'Annulée par le client.', 'CANCELLED');
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
     * Réconcilie les paiements en attente (planifié chaque minute) et revérifie
     * les demandes expirées/annulées récentes (validation tardive).
     *
     * @return int Nombre de paiements dont le statut a changé.
     */
    public function reconcilePending(): int
    {
        $count = 0;

        $candidates = Payment::query()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::InProgress->value])
            ->get()
            ->merge($this->recoverable());

        foreach ($candidates as $payment) {
            $before = $payment->status;

            try {
                $after = $this->verify($payment)->status;
            } catch (\Throwable $e) {
                Log::error('PayMe : erreur de réconciliation.', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($after !== $before) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Applique un résultat normalisé de PayMe (`success` | `failed` | `pending`).
     *
     * @param  array<string, mixed>  $result
     */
    private function applyResult(Payment $payment, array $result): Payment
    {
        return match ($result['state']) {
            'success' => $this->markPaid($payment, PaymentStatus::Paid, $result),
            'failed' => $this->fail($payment, $result['message'] ?? null, $result['gateway_status'] ?? null),
            default => tap($payment)->update(array_filter([
                'gateway_status' => $result['gateway_status'] ?? null,
                'provider' => $result['provider'] ?? null,
            ])),
        };
    }

    /**
     * Passe le paiement en échec (statut brut et motif conservés).
     */
    private function fail(Payment $payment, ?string $error = null, ?string $gatewayStatus = null): Payment
    {
        $before = $payment->status;

        if (! $payment->statusEnum()->canTransitionTo(PaymentStatus::Failed)) {
            return $payment;
        }

        $payment->update([
            'status' => PaymentStatus::Failed->value,
            'gateway_status' => $gatewayStatus ?? $payment->gateway_status,
            'metadata' => array_merge($payment->metadata ?? [], array_filter(['error' => $error])),
        ]);

        AuditService::record('payment.failed', $payment, ['status' => $before], [
            'status' => PaymentStatus::Failed->value,
            'gateway_status' => $payment->gateway_status,
            'error' => $error,
        ]);

        return $payment->fresh();
    }

    /**
     * Demande expirée ou annulée, référencée chez PayMe et encore dans la
     * fenêtre de revérification.
     */
    private function isRecoverable(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Failed->value
            && filled($payment->gateway_reference)
            && in_array(strtoupper((string) $payment->gateway_status), ['EXPIRED', 'CANCELLED'], true)
            && $payment->created_at?->gte(now()->subMinutes($this->recoveryWindow($payment)));
    }

    /**
     * Demandes expirées/annulées candidates à la revérification (délai
     * maximal de 60 min + fenêtre de revérification ; affiné par `isRecoverable`).
     *
     * @return Collection<int, Payment>
     */
    private function recoverable()
    {
        return Payment::query()
            ->where('status', PaymentStatus::Failed->value)
            ->whereNotNull('gateway_reference')
            ->whereIn('gateway_status', ['EXPIRED', 'CANCELLED'])
            ->where('created_at', '>=', now()->subMinutes(60 + (int) config('payme.recovery_minutes', 60)))
            ->get()
            ->filter(fn (Payment $payment) => $this->isRecoverable($payment));
    }

    /**
     * Revérifie une demande expirée/annulée : si PayMe confirme le succès
     * (validation tardive sur le téléphone), le paiement repasse en `paid`.
     * Seule exception à la machine à états (`failed → paid`), auditée.
     */
    private function recover(Payment $payment): Payment
    {
        $gateway = PaymentGateway::active();

        if (! $gateway) {
            return $payment;
        }

        try {
            $result = $this->client->paymentStatus($gateway, $payment->gateway_reference);
        } catch (\Throwable) {
            return $payment;
        }

        $payment->update(['checked_at' => now(), 'checks' => $payment->checks + 1]);

        if ($result['state'] !== 'success') {
            return $payment;
        }

        AuditService::record('payment.recovered', $payment, [
            'status' => PaymentStatus::Failed->value,
            'gateway_status' => $payment->gateway_status,
        ], ['status' => PaymentStatus::Paid->value]);

        return $this->markPaid($payment, PaymentStatus::Paid, $result);
    }

    private function recoveryWindow(Payment $payment): int
    {
        $timeout = (int) ($payment->metadata['timeout_minutes'] ?? config('payme.timeout_minutes', 15));

        return $timeout + (int) config('payme.recovery_minutes', 60);
    }

    private function isExpired(Payment $payment, PaymentGateway $gateway): bool
    {
        $timeout = (int) ($payment->metadata['timeout_minutes'] ?? $gateway->timeoutMinutes());

        return (bool) $payment->created_at?->lte(now()->subMinutes($timeout));
    }

    /**
     * Marque le paiement comme payé/déposé et active l'abonnement.
     *
     * @param  array<string, mixed>  $result  Résultat PayMe normalisé (reçu, opérateur…).
     */
    private function markPaid(Payment $payment, PaymentStatus $status, array $result = []): Payment
    {
        $oldStatus = $payment->status;

        // Verrou de ligne : la page (polling) et la planification peuvent
        // confirmer le même paiement en même temps ; une seule active l'abonnement.
        $updated = DB::transaction(function () use ($payment, $status, $result) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === $status->value) {
                return false;
            }

            $locked->update(array_filter([
                'status' => $status->value,
                'paid_at' => $locked->paid_at ?? now(),
                'gateway_status' => $result['gateway_status'] ?? null,
                'provider' => $result['provider'] ?? null,
                'receipt_url' => $result['receipt_url'] ?? null,
            ]));

            $this->activateSubscription($locked);

            return true;
        });

        $payment = $payment->fresh();

        if ($updated) {
            AuditService::record("payment.{$status->value}", $payment, ['status' => $oldStatus], [
                'status' => $status->value,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ]);
        }

        return $payment;
    }

    /**
     * Active (ou prolonge) l'abonnement associé au paiement réussi.
     */
    private function activateSubscription(Payment $payment): void
    {
        $packId = $payment->metadata['pack_id'] ?? null;

        if (! $packId || $payment->subscription_id) {
            return;
        }

        $pack = Pack::query()->find($packId);

        if (! $pack || ! $payment->tenant) {
            return;
        }

        $period = $payment->metadata['billing_period'] ?? $pack->billing_period;

        // `renew` prolonge depuis l'échéance courante si l'école est encore
        // abonnée (aucune période payée n'est perdue), sinon démarre maintenant.
        $subscription = $this->subscriptions->renew($payment->tenant, $pack, $period);

        $payment->update(['subscription_id' => $subscription->id]);
    }
}
