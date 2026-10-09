<?php

namespace App\Services;

use App\Models\SmsCredential;
use Illuminate\Support\Facades\Http;

/**
 * Client HTTP Nexah Bulk SMS.
 *
 * Les crédences (`user`/`password`) proviennent du SmsCredential du tenant ; le
 * mot de passe est déchiffré à la lecture (cast `encrypted`).
 *
 * ⚠️ L'URL de base et l'enveloppe de réponse exactes de Nexah doivent être
 * confirmées (voir §10 du plan). La détection d'échec est volontairement
 * défensive.
 */
class NexahSmsClient
{
    /**
     * Envoie un SMS vers un ou plusieurs destinataires.
     *
     * @param  list<string>  $recipients
     * @return array{success: bool, provider_ref?: string|null, error?: string|null}
     */
    public function send(SmsCredential $credential, string $message, array $recipients): array
    {
        $response = Http::timeout((int) config('nexah.timeout', 15))
            ->asForm()
            ->post($this->url('send_path'), [
                'user' => $credential->user,
                'password' => $credential->password,
                'senderid' => $credential->sender_id,
                'sms' => $message,
                'mobiles' => implode(',', $recipients),
            ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Nexah HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        $body = $response->json();

        if ($this->looksFailed($body)) {
            return ['success' => false, 'error' => $this->extractError($body)];
        }

        return [
            'success' => true,
            'provider_ref' => $body['message_id'] ?? $body['id'] ?? $body['reference'] ?? null,
        ];
    }

    /**
     * Récupère le solde de crédits SMS du compte.
     *
     * @return array{success: bool, balance?: float|null, error?: string|null}
     */
    public function getBalance(SmsCredential $credential): array
    {
        $response = Http::timeout((int) config('nexah.timeout', 15))
            ->asForm()
            ->post($this->url('balance_path'), [
                'user' => $credential->user,
                'password' => $credential->password,
            ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Nexah HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        $body = $response->json();

        if ($this->looksFailed($body)) {
            return ['success' => false, 'error' => $this->extractError($body)];
        }

        $balance = $body['balance'] ?? $body['credit'] ?? $body['remaining'] ?? null;

        return [
            'success' => true,
            'balance' => $balance !== null ? (float) $balance : null,
        ];
    }

    /**
     * Construit l'URL d'un endpoint Nexah.
     */
    private function url(string $pathKey): string
    {
        return rtrim((string) config('nexah.base_url'), '/')
            .'/'.ltrim((string) config("nexah.{$pathKey}"), '/');
    }

    /**
     * Détection défensive d'une réponse d'échec.
     *
     * @param  array<string, mixed>|null  $body
     */
    private function looksFailed(?array $body): bool
    {
        if (! is_array($body)) {
            return false;
        }

        $status = strtolower((string) ($body['status'] ?? $body['code'] ?? ''));

        return in_array($status, ['0', 'failed', 'error', 'ko', 'false'], true);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function extractError(array $body): string
    {
        return (string) ($body['message'] ?? $body['error'] ?? $body['description'] ?? json_encode($body));
    }
}
