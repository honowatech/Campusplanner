<?php

namespace App\Services;

use App\Enums\V1\PaymentStatus;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cycle de vie des paiements PayMe (MamoniPay).
 *
 * Idempotence :
 * - DB : `gateway_reference`, `external_reference`, `idempotency_key` UNIQUE.
 * - Service : machine à états `PaymentStatus` (transition illégale = no-op).
 * - API : header `Idempotency-Key` sur le checkout (rejeu → paiement existant).
 *
 * L'API PayMe n'a ni notification, ni annulation, ni expiration signalée :
 * la confirmation repose uniquement sur la vérification active
 * (`reconcilePending()` chaque minute, cf. routes/console.php).
 */
class PaymentService
{
    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_CANCELLED = 'CANCELLED';

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

        $timeoutMinutes = $this->timeoutMinutes($gateway);

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
                'timeout_minutes' => $timeoutMinutes,
                'expires_at' => now()->addMinutes($timeoutMinutes)->toIso8601String(),
            ],
        ]);

        AuditService::record('payment.created', $payment, [], [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'pack_id' => $pack->id,
        ]);

        $payload = [
            'amount' => (int) round((float) $pack->price),
            'phone_number' => $this->client->normalizePhone($phone),
            'external_reference' => $payment->external_reference,
            'client_name' => $tenant->name ?: 'Campus Planner',
        ];

        // Envoyé uniquement s'il est explicitement configuré (sinon le réglage
        // du compte marchand s'applique).
        if ($gateway->client_fees_rate !== null) {
            $payload['client_fees_rate'] = (int) $gateway->client_fees_rate;
        }

        try {
            $result = $this->client->initiatePayment($gateway, $payload);
        } catch (\Throwable $e) {
            Log::error('PayMe : exception lors de l\'initiation du paiement.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return $this->fail($payment, $e->getMessage());
        }

        if ($result['state'] === 'failed') {
            Log::warning('PayMe : initiation du paiement refusée.', [
                'payment_id' => $payment->id,
                'error' => $result['message'],
                'gateway_status' => $result['gateway_status'],
            ]);

            return $this->fail($payment, $result['message'] ?? 'Paiement refusé par PayMe.', $result['gateway_status']);
        }

        if (blank($result['gateway_reference'])) {
            Log::error('PayMe : initiation sans gateway_reference.', [
                'payment_id' => $payment->id,
                'raw' => $result['raw'],
            ]);

            return $this->fail($payment, 'PayMe n\'a pas renvoyé de référence de transaction.', $result['gateway_status']);
        }

        $payment->update([
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $result['gateway_reference'],
            'gateway_status' => $result['gateway_status'],
            'provider' => $result['provider'],
            'checked_at' => now(),
        ]);

        AuditService::record('payment.in_progress', $payment, ['status' => PaymentStatus::Pending->value], [
            'status' => PaymentStatus::InProgress->value,
            'gateway_reference' => $result['gateway_reference'],
            'gateway_status' => $result['gateway_status'],
        ]);

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
     * Vérifie un paiement auprès de PayMe et applique le résultat.
     *
     * @throws \RuntimeException issue incertaine (404 TXN-002, 5xx, réseau)
     */
    public function refresh(Payment $payment): Payment
    {
        if (blank($payment->gateway_reference)) {
            return $payment;
        }

        $gateway = PaymentGateway::active();

        if (! $gateway) {
            return $payment;
        }

        $result = $this->client->paymentStatus($gateway, $payment->gateway_reference);

        return $this->applyPaymeStatus($payment, $result);
    }

    /**
     * Le client renonce à attendre : dernière vérification auprès de PayMe,
     * puis échec « annulée par le client ». L'invite peut rester sur le
     * téléphone : le paiement est revérifié pendant `payme.recovery_minutes`.
     */
    public function cancel(Payment $payment): Payment
    {
        if (! in_array($payment->statusEnum(), [PaymentStatus::Pending, PaymentStatus::InProgress], true)) {
            return $payment;
        }

        if (filled($payment->gateway_reference)) {
            try {
                $payment = $this->refresh($payment);
            } catch (\Throwable $e) {
                Log::warning('PayMe : vérification impossible avant annulation.', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if (! in_array($payment->statusEnum(), [PaymentStatus::Pending, PaymentStatus::InProgress], true)) {
                return $payment;
            }
        }

        return $this->fail($payment, 'Demande annulée par le client.', self::STATUS_CANCELLED);
    }

    /**
     * Réconcilie les paiements en attente (polling, chaque minute).
     *
     * 1. Demandes sans `gateway_reference` (jamais initiées chez PayMe) plus
     *    vieilles que `payme.uninitiated_timeout_minutes` → échec.
     * 2. Demandes en cours : vérification ; au-delà du délai configuré,
     *    dernière vérification puis échec `EXPIRED`.
     * 3. Demandes `EXPIRED` / `CANCELLED` récentes : revérifiées pendant
     *    `payme.recovery_minutes` ; un `Successful` tardif repasse en payé.
     *
     * Une exception du client n'arrête pas la boucle.
     *
     * @return int Nombre de paiements dont le statut a changé.
     */
    public function reconcilePending(): int
    {
        $gateway = PaymentGateway::active();

        if (! $gateway) {
            return 0;
        }

        $count = 0;
        $open = [PaymentStatus::Pending->value, PaymentStatus::InProgress->value];

        // 1. Jamais initiées chez PayMe.
        $uninitiatedBefore = now()->subMinutes((int) config('payme.uninitiated_timeout_minutes', 2));

        $orphans = Payment::query()
            ->whereIn('status', $open)
            ->whereNull('gateway_reference')
            ->where('created_at', '<=', $uninitiatedBefore)
            ->get();

        foreach ($orphans as $payment) {
            $this->fail($payment, 'Demande jamais initiée chez PayMe (aucune référence de transaction).');
            $count++;
        }

        // Lot des demandes expirées/annulées à revérifier (cf. étape 3).
        $recoveryMinutes = (int) config('payme.recovery_minutes', 60);
        $recoverableSince = now()->subMinutes($recoveryMinutes);

        $recoverable = Payment::query()
            ->where('status', PaymentStatus::Failed->value)
            ->whereIn('gateway_status', [self::STATUS_EXPIRED, self::STATUS_CANCELLED])
            ->whereNotNull('gateway_reference')
            ->where('updated_at', '>=', $recoverableSince->copy()->subHours(2))
            ->get()
            ->filter(function (Payment $payment) use ($recoverableSince) {
                $failedAt = $payment->metadata['failed_at'] ?? null;

                return $failedAt && now()->parse($failedAt)->gte($recoverableSince);
            });

        // 2. En cours chez PayMe.
        $timeoutMinutes = $this->timeoutMinutes($gateway);

        $pending = Payment::query()
            ->whereIn('status', $open)
            ->whereNotNull('gateway_reference')
            ->get();

        foreach ($pending as $payment) {
            $before = $payment->status;
            $expired = $payment->created_at && $payment->created_at->lte(now()->subMinutes($timeoutMinutes));

            try {
                $payment = $this->refresh($payment);
            } catch (\Throwable $e) {
                Log::warning('PayMe : vérification impossible.', [
                    'payment_id' => $payment->id,
                    'gateway_reference' => $payment->gateway_reference,
                    'error' => $e->getMessage(),
                ]);

                $payment->forceFill(['checked_at' => now(), 'checks' => $payment->checks + 1])->save();
            }

            if ($payment->status === $before && $expired) {
                $payment = $this->fail($payment, 'Aucune confirmation dans le délai imparti ('.$timeoutMinutes.' min).', self::STATUS_EXPIRED);
            }

            if ($payment->status !== $before) {
                $count++;
            }
        }

        // 3. Validation tardive après expiration ou annulation (lot figé en
        //    début de passe : une demande expirée à cette passe est revérifiée
        //    à partir de la suivante).
        foreach ($recoverable as $payment) {
            try {
                $result = $this->client->paymentStatus($gateway, $payment->gateway_reference);
            } catch (\Throwable $e) {
                Log::warning('PayMe : revérification impossible.', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $payment->forceFill(['checked_at' => now(), 'checks' => $payment->checks + 1])->save();

            if ($result['state'] === 'success') {
                $this->recover($payment, $result);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validation tardive : un paiement `Failed` (EXPIRED/CANCELLED) confirmé
     * `Successful` par PayMe repasse en payé et active l'abonnement.
     *
     * Seul contournement autorisé de la machine à états (audité `payment.recovered`).
     *
     * @param  array{gateway_status?: string|null, provider?: string|null, receipt_url?: string|null}  $result
     */
    public function recover(Payment $payment, array $result = []): Payment
    {
        if ($payment->statusEnum() !== PaymentStatus::Failed) {
            return $payment;
        }

        $payment->fill([
            'gateway_status' => $result['gateway_status'] ?? $payment->gateway_status,
            'provider' => $result['provider'] ?? $payment->provider,
            'receipt_url' => $result['receipt_url'] ?? $payment->receipt_url,
            'metadata' => array_merge($payment->metadata ?? [], ['recovered_at' => now()->toIso8601String()]),
        ]);

        return $this->markPaid($payment, PaymentStatus::Paid, 'payment.recovered');
    }

    /**
     * Applique un résultat normalisé de PayMe au paiement.
     *
     * @param  array{state: string, gateway_status: string|null, provider: string|null, receipt_url: string|null, message: string|null}  $result
     */
    private function applyPaymeStatus(Payment $payment, array $result): Payment
    {
        $payment->fill([
            'gateway_status' => $result['gateway_status'] ?? $payment->gateway_status,
            'provider' => $result['provider'] ?? $payment->provider,
            'receipt_url' => $result['receipt_url'] ?? $payment->receipt_url,
            'checked_at' => now(),
            'checks' => $payment->checks + 1,
        ]);

        return match ($result['state']) {
            'success' => $this->transition($payment, PaymentStatus::Paid),
            'failed' => $this->fail($payment, $result['message'] ?? 'Paiement refusé par PayMe.', $result['gateway_status']),
            default => tap($payment)->save(),
        };
    }

    /**
     * Passe le paiement en échec avec le motif (metadata.error) et l'audit.
     */
    private function fail(Payment $payment, string $reason, ?string $gatewayStatus = null): Payment
    {
        $current = $payment->statusEnum();

        if (! $current->canTransitionTo(PaymentStatus::Failed)) {
            $payment->save();

            return $payment;
        }

        $payment->fill([
            'status' => PaymentStatus::Failed->value,
            'gateway_status' => $gatewayStatus ?? $payment->gateway_status,
            'metadata' => array_merge($payment->metadata ?? [], [
                'error' => $reason,
                'failed_at' => now()->toIso8601String(),
            ]),
        ])->save();

        AuditService::record('payment.failed', $payment, ['status' => $current->value], [
            'status' => PaymentStatus::Failed->value,
            'gateway_status' => $payment->gateway_status,
            'error' => $reason,
        ]);

        return $payment->fresh();
    }

    /**
     * Marque le paiement comme payé/déposé et active l'abonnement.
     */
    private function markPaid(Payment $payment, PaymentStatus $status, ?string $event = null): Payment
    {
        if ($payment->status === $status->value) {
            $payment->save();

            return $payment;
        }

        $oldStatus = $payment->status;

        DB::transaction(function () use ($payment, $status) {
            $payment->fill([
                'status' => $status->value,
                'paid_at' => $payment->paid_at ?? now(),
            ])->save();

            $this->activateSubscription($payment);
        });

        AuditService::record($event ?? "payment.{$status->value}", $payment, ['status' => $oldStatus], [
            'status' => $status->value,
            'gateway_status' => $payment->gateway_status,
            'provider' => $payment->provider,
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
     * Délai d'attente d'une confirmation (minutes) : réglage passerelle, sinon config.
     */
    public function timeoutMinutes(?PaymentGateway $gateway = null): int
    {
        $minutes = (int) ($gateway?->timeout_minutes ?: config('payme.timeout_minutes', 15));

        return max(2, $minutes);
    }
}
