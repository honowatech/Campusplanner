<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Envoi de SMS par tenant, avec journalisation et idempotence.
 *
 * Idempotence (cf. §7 du plan) :
 * - `idempotency_key` (header client) : rejeu → renvoie le log existant.
 * - `dedup_key` (hash du contenu) : un même SMS (tenant + destinataire +
 *   message) n'est jamais envoyé deux fois.
 * - Contrainte UNIQUE sur `dedup_key` + `firstOrCreate` : garantie DB.
 */
class SmsService
{
    public function __construct(protected NexahSmsClient $client) {}

    /**
     * Envoie un SMS pour le tenant.
     */
    public function send(Tenant $tenant, string $to, string $message, ?string $idempotencyKey = null): SmsLog
    {
        $credential = $tenant->smsCredential()->where('is_active', true)->first();

        if (! $credential) {
            throw new \RuntimeException('Aucune configuration SMS active pour ce compte.');
        }

        if ($idempotencyKey) {
            $existing = SmsLog::query()
                ->where('tenant_id', $tenant->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $dedupKey = hash('sha256', "{$tenant->id}:{$credential->sender_id}:{$to}:{$message}");

        $log = SmsLog::query()->firstOrCreate(
            ['dedup_key' => $dedupKey],
            [
                'tenant_id' => $tenant->id,
                'to' => $to,
                'message' => $message,
                'status' => SmsLog::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
            ]
        );

        if (! $log->wasRecentlyCreated) {
            return $log;
        }

        try {
            $result = $this->client->send($credential, $message, [$to]);

            $log->update([
                'status' => $result['success'] ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
                'provider_ref' => $result['provider_ref'] ?? null,
                'error' => $result['error'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => SmsLog::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    /**
     * Récupère le solde SMS du tenant et le met en cache.
     *
     * @return array{success: bool, balance?: float|null, error?: string|null}
     */
    public function getBalance(Tenant $tenant): array
    {
        $credential = $tenant->smsCredential()->where('is_active', true)->first();

        if (! $credential) {
            throw new \RuntimeException('Aucune configuration SMS active pour ce compte.');
        }

        $result = $this->client->getBalance($credential);

        if ($result['success'] && isset($result['balance'])) {
            $credential->update(['balance_cached' => $result['balance']]);
        }

        return $result;
    }
}
