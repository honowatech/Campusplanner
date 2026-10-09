<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client HTTP PayMe (MamoniPay).
 *
 * Le JWT d'authentification est mis en cache (clé par gateway) et régénéré à
 * expiration (cf. §8 du plan : « JWT PayMe mis en cache, régénéré sur 401 »).
 *
 * ⚠️ Les enveloppes de réponse exactes de PayMe doivent être confirmées (voir
 * §10 du plan). La lecture est volontairement défensive.
 */
class PaymeClient
{
    /**
     * Authentifie la plateforme et retourne un JWT (mis en cache).
     */
    public function token(PaymentGateway $gateway): string
    {
        return Cache::remember(
            "payme_token:{$gateway->id}",
            now()->addMinutes((int) config('payme.token_ttl', 50)),
            function () use ($gateway) {
                return $this->requestToken($gateway);
            }
        );
    }

    /**
     * Oublie le token en cache (régénération forcée).
     */
    public function forgetToken(PaymentGateway $gateway): void
    {
        Cache::forget("payme_token:{$gateway->id}");
    }

    /**
     * Initie un paiement (mobile money) et retourne la référence passerelle.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, gateway_reference?: string|null, payment_url?: string|null, error?: string|null}
     */
    public function initiatePayment(PaymentGateway $gateway, string $token, array $payload): array
    {
        $response = Http::timeout((int) config('payme.timeout', 15))
            ->withToken($token)
            ->post($this->url($gateway, 'init_payment_path'), $payload);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'PayMe HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        $body = $response->json();

        return [
            'success' => true,
            'gateway_reference' => $body['gateway_reference'] ?? $body['reference'] ?? $body['transaction_id'] ?? null,
            'payment_url' => $body['payment_url'] ?? $body['paymelink'] ?? $body['url'] ?? null,
        ];
    }

    /**
     * Vérifie le statut d'une transaction auprès de PayMe.
     *
     * @return array{success: bool, status?: string|null, error?: string|null}
     */
    public function paymentStatus(PaymentGateway $gateway, string $token, string $gatewayReference): array
    {
        $response = Http::timeout((int) config('payme.timeout', 15))
            ->withToken($token)
            ->post($this->url($gateway, 'payment_status_path'), [
                'gateway_reference' => $gatewayReference,
            ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'PayMe HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        $body = $response->json();

        return [
            'success' => true,
            'status' => $body['status'] ?? $body['transaction_status'] ?? null,
        ];
    }

    /**
     * Authentifie auprès de PayMe et extrait le JWT.
     */
    private function requestToken(PaymentGateway $gateway): string
    {
        $response = Http::timeout((int) config('payme.timeout', 15))
            ->post($this->url($gateway, 'login_path'), [
                'user_name' => $gateway->user_name,
                'password' => $gateway->password,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('PayMe auth HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200));
        }

        $body = $response->json();

        $token = $body['token'] ?? $body['access_token'] ?? $body['jwt'] ?? null;

        if (! $token) {
            throw new \RuntimeException('Réponse d\'authentification PayMe sans jeton.');
        }

        return (string) $token;
    }

    /**
     * Construit l'URL d'un endpoint PayMe selon le mode (sandbox/live).
     */
    private function url(PaymentGateway $gateway, string $pathKey): string
    {
        $base = $gateway->endpoint
            ?: config($gateway->mode === 'live' ? 'payme.live_base_url' : 'payme.sandbox_base_url');

        return rtrim((string) $base, '/')
            .'/'.ltrim((string) config("payme.{$pathKey}"), '/');
    }
}
