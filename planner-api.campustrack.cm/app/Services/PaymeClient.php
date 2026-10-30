<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymeClient
{
    /**
     * Authentifie la plateforme et retourne le jeton d'accès (mis en cache).
     */
    public function token(PaymentGateway $gateway): string
    {
        return Cache::remember(
            "payme_token:{$gateway->id}",
            now()->addMinutes((int) config('payme.token_ttl', 50)),
            fn () => $this->authenticate($gateway)
        );
    }

    /**
     * Oublie le jeton en cache (régénération forcée, ex. crédences modifiées).
     */
    public function forgetToken(PaymentGateway $gateway): void
    {
        Cache::forget("payme_token:{$gateway->id}");
    }

    /**
     * Normalise un numéro de téléphone au format international attendu par
     * PayMe : sans « + », sans espaces, indicatif pays `237` préfixé si absent.
     */
    public function normalizePhone(string $phone): string
    {
        // Ne conserve que les chiffres.
        $digits = (string) preg_replace('/\D+/', '', $phone);

        // Préfixe international « 00 » → indicatif pays nu.
        $digits = (string) preg_replace('/^00/', '', $digits);

        // Numéro local camerounais (9 chiffres) → préfixe « 237 ».
        if (strlen($digits) === 9) {
            $digits = '237'.$digits;
        }

        return $digits;
    }

    /**
     * Initie un paiement (mobile money) et retourne la référence passerelle.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, gateway_reference?: string|null, external_reference?: string|null, error?: string|null}
     */
    public function initiatePayment(PaymentGateway $gateway, array $payload): array
    {
        $response = $this->post($gateway, 'init_payment_path', $payload);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'PayMe HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        $body = $response->json() ?? [];
        $data = $body['data'] ?? $body;

        return [
            'success' => true,
            'gateway_reference' => $data['gateway_reference'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
        ];
    }

    /**
     * Vérifie le statut d'une transaction auprès de PayMe.
     *
     * @return array{success: bool, status?: string|null, error?: string|null}
     */
    public function paymentStatus(PaymentGateway $gateway, string $gatewayReference): array
    {
        $response = $this->post($gateway, 'payment_status_path', [
            'gateway_reference' => $gatewayReference,
        ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'PayMe HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        $body = $response->json() ?? [];
        $data = $body['data'] ?? $body;

        return [
            'success' => true,
            'status' => $data['status'] ?? null,
        ];
    }

    /**
     * Authentifie auprès de PayMe et extrait le jeton (`data.token`).
     */
    private function authenticate(PaymentGateway $gateway): string
    {
        $response = Http::timeout((int) config('payme.timeout', 30))
            ->post($this->url($gateway, 'login_path'), [
                'user_name' => $gateway->user_name,
                'password' => $gateway->password,
            ]);

        if ($response->failed()) {
            Log::error('PayMe : échec de l\'authentification.', [
                'gateway_id' => $gateway->id,
                'status' => $response->status(),
                'response' => mb_substr((string) $response->body(), 0, 500),
            ]);

            throw new \RuntimeException('PayMe auth HTTP '.$response->status().': '.mb_substr($response->body(), 0, 200));
        }

        $body = $response->json() ?? [];
        $token = $body['data']['token'] ?? $body['token'] ?? null;

        if (! $token) {
            Log::error('PayMe : authentification sans jeton.', [
                'gateway_id' => $gateway->id,
                'response' => mb_substr((string) $response->body(), 0, 500),
            ]);

            throw new \RuntimeException('Réponse d\'authentification PayMe sans jeton.');
        }

        return (string) $token;
    }

    /**
     * Envoie une requête authentifiée, avec ré-authentification sur `401`.
     */
    private function post(PaymentGateway $gateway, string $pathKey, array $payload): Response
    {
        $response = $this->send($gateway, $pathKey, $this->token($gateway), $payload);

        if ($response->status() === 401) {
            $this->forgetToken($gateway);

            $response = $this->send($gateway, $pathKey, $this->token($gateway), $payload);
        }

        return $response;
    }

    /**
     * Envoie la requête avec le jeton fourni (JSON + Bearer).
     */
    private function send(PaymentGateway $gateway, string $pathKey, string $token, array $payload): Response
    {
        return Http::timeout((int) config('payme.timeout', 15))
            ->withToken($token)
            ->accept('application/json')
            ->contentType('application/json')
            ->post($this->url($gateway, $pathKey), $payload);
    }

    /**
     * Construit l'URL d'un endpoint PayMe (base + chemin configuré).
     */
    private function url(PaymentGateway $gateway, string $pathKey): string
    {
        $base = $gateway->endpoint
            ?: ($gateway->mode === 'live'
                ? config('payme.live_base_url')
                : config('payme.sandbox_base_url'));

        $url = rtrim((string) $base, '/')
            .'/'.ltrim((string) config("payme.{$pathKey}"), '/');

        Log::debug('PayMe request URL', [
            'gateway_id' => $gateway->id,
            'path' => $pathKey,
            'url' => $url,
        ]);

        return $url;
    }
}
