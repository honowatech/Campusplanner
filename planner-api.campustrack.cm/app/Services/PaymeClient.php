<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP de l'API Partenaire PayMe (MamoniPay).
 *
 * Transposé de la passerelle ZoomLab qui fonctionne en production
 * (cf. docs/FEUILLE_DE_ROUTE_PAYME.md §1). Toute réponse d'initiation ou
 * de vérification est normalisée en :
 *
 *   ['state' => 'pending'|'success'|'failed', 'gateway_reference', 'gateway_status',
 *    'provider', 'receipt_url', 'message', 'raw']
 *
 * Un 404 (TXN-002), un 5xx ou une erreur réseau lèvent une exception :
 * l'issue est incertaine, l'appelant décide quoi en faire.
 */
class PaymeClient
{
    public const DUMMY_REFERENCE = '00000000-0000-0000-0000-000000000000';

    /**
     * Adresse de base de l'API, normalisée (sans « / » final).
     *
     * @throws \RuntimeException si l'adresse pointe sur la documentation ou se termine par `/vN`.
     */
    public function baseUrl(PaymentGateway $gateway): string
    {
        return self::normalizeBaseUrl((string) ($gateway->endpoint ?: config('payme.base_url')));
    }

    /**
     * Normalise une adresse de base et refuse les adresses manifestement fausses.
     */
    public static function normalizeBaseUrl(string $base): string
    {
        $base = rtrim(trim($base), '/');

        if ($base === '') {
            $base = rtrim((string) config('payme.base_url'), '/');
        }

        if (self::isInvalidBaseUrl($base)) {
            throw new \RuntimeException("Adresse PayMe invalide ({$base}) : indiquer l'adresse de base de l'API, ex. https://console-v2.mamonipay.me/api");
        }

        return $base;
    }

    /**
     * Une adresse de documentation (`/docs`) ou déjà versionnée (`/v1`) ne peut pas fonctionner.
     */
    public static function isInvalidBaseUrl(string $base): bool
    {
        return (bool) preg_match('#/docs(/|$)|/v\d+/?$#i', rtrim(trim($base), '/'));
    }

    public function loginUrl(PaymentGateway $gateway): string
    {
        return $this->baseUrl($gateway).config('payme.endpoints.login');
    }

    /**
     * Jeton d'accès (Bearer), mis en cache jusqu'à `data.expires_at` moins une minute.
     */
    public function token(PaymentGateway $gateway): string
    {
        $key = $this->tokenKey($gateway);

        if ($token = Cache::get($key)) {
            return (string) $token;
        }

        $login = $this->login($gateway);

        if (! $login['ok']) {
            throw new \RuntimeException($login['message']);
        }

        $ttl = 3000;
        if ($login['expires_at']) {
            try {
                $ttl = (int) now()->diffInSeconds(CarbonImmutable::parse($login['expires_at']), false);
            } catch (\Throwable) {
                // expires_at illisible : on garde le TTL par défaut.
            }
        }

        Cache::put($key, $login['token'], max(60, $ttl - 60));

        return $login['token'];
    }

    /**
     * Oublie le jeton en cache (crédences modifiées, 401…).
     */
    public function forgetToken(PaymentGateway $gateway): void
    {
        try {
            Cache::forget($this->tokenKey($gateway));
        } catch (\RuntimeException) {
            // Adresse invalide : aucun jeton n'a pu être mis en cache.
        }
    }

    /**
     * Tente une connexion et décrit précisément le résultat (sans lever).
     *
     * @return array{ok: bool, status: int, message: string, url: string, effective_url: string|null, token: string|null, expires_at: string|null}
     */
    public function login(PaymentGateway $gateway): array
    {
        $url = $this->loginUrl($gateway);

        try {
            $response = $this->http()->post($url, [
                'login' => $gateway->user_name,
                'password' => $gateway->password,
            ]);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Connexion PayMe impossible : '.$e->getMessage().' — URL appelée : '.$url,
                'url' => $url,
                'effective_url' => null,
                'token' => null,
                'expires_at' => null,
            ];
        }

        $data = (array) $response->json();
        $token = Arr::get($data, 'data.access_token') ?? Arr::get($data, 'access_token');
        $effective = (string) $response->effectiveUri();
        $redirected = $effective !== '' && $effective !== $url ? $effective : null;

        if (! $response->successful() || Arr::get($data, 'success') === false || ! $token) {
            $message = sprintf(
                'Connexion PayMe refusée (%d)%s — URL appelée : %s%s',
                $response->status(),
                ($m = self::errorMessage($data)) ? ' : '.$m : '',
                $url,
                $redirected ? ' (redirigée vers '.$redirected.')' : '',
            );

            Log::error('PayMe : échec de l\'authentification.', [
                'gateway_id' => $gateway->id,
                'status' => $response->status(),
                'url' => $url,
                'response' => mb_substr((string) $response->body(), 0, 500),
            ]);

            return [
                'ok' => false,
                'status' => $response->status(),
                'message' => $message,
                'url' => $url,
                'effective_url' => $redirected,
                'token' => null,
                'expires_at' => null,
            ];
        }

        $expiresAt = Arr::get($data, 'data.expires_at');

        return [
            'ok' => true,
            'status' => $response->status(),
            'message' => 'Connexion PayMe réussie.',
            'url' => $url,
            'effective_url' => $redirected,
            'token' => (string) $token,
            'expires_at' => is_string($expiresAt) ? $expiresAt : null,
        ];
    }

    /**
     * Test de connexion (bouton « Tester la connexion » / doctor) : jeton
     * vidé avant, puis login avec les identifiants enregistrés.
     *
     * @return array{ok: bool, status: int, message: string, url: string|null, effective_url: string|null, expires_at: string|null}
     */
    public function testConnection(PaymentGateway $gateway): array
    {
        try {
            $this->forgetToken($gateway);
            $login = $this->login($gateway);
        } catch (\RuntimeException $e) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => $e->getMessage(),
                'url' => null,
                'effective_url' => null,
                'expires_at' => null,
            ];
        }

        if ($login['ok']) {
            Cache::put($this->tokenKey($gateway), $login['token'], 60);
        }

        return Arr::except($login, ['token']);
    }

    /**
     * Normalise un numéro de téléphone au format attendu par PayMe :
     * chiffres seuls, indicatif pays `237` préfixé si absent.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);
        $digits = (string) preg_replace('/^00/', '', $digits);

        if (strlen($digits) === 9) {
            $digits = '237'.$digits;
        }

        return $digits;
    }

    /**
     * Initie un paiement mobile money.
     *
     * @param  array{amount: int|float|string, phone_number: string, external_reference: string, client_name?: string|null, client_fees_rate?: int|null}  $payload
     * @return array{state: string, gateway_reference: string|null, gateway_status: string|null, provider: string|null, receipt_url: string|null, message: string|null, raw: array<string, mixed>}
     */
    public function initiatePayment(PaymentGateway $gateway, array $payload): array
    {
        $body = [
            'channel' => 'SELLER_DIRECT',
            'phone_number' => $payload['phone_number'],
            'amount' => (int) $payload['amount'],
            'external_reference' => $payload['external_reference'],
            'client_name' => $payload['client_name'] ?? null,
        ];

        if (filled($payload['client_fees_rate'] ?? null)) {
            $body['client_fees_rate'] = (int) $payload['client_fees_rate'];
        }

        $url = $this->baseUrl($gateway).config('payme.endpoints.init');

        $response = $this->authorized($gateway, fn (PendingRequest $http) => $http->post($url, $body));

        return $this->normalize($response);
    }

    /**
     * Vérifie une transaction par sa `gateway_reference` (dans l'URL, corps vide).
     *
     * @return array{state: string, gateway_reference: string|null, gateway_status: string|null, provider: string|null, receipt_url: string|null, message: string|null, raw: array<string, mixed>}
     */
    public function paymentStatus(PaymentGateway $gateway, string $gatewayReference): array
    {
        if (blank($gatewayReference)) {
            throw new \RuntimeException('Aucune gateway_reference : la demande n\'a pas été initiée chez PayMe.');
        }

        $url = $this->verifyUrl($gateway, $gatewayReference);

        $response = $this->authorized($gateway, fn (PendingRequest $http) => $http->post($url));

        return $this->normalize($response);
    }

    public function verifyUrl(PaymentGateway $gateway, string $gatewayReference): string
    {
        return $this->baseUrl($gateway)
            .str_replace('{reference}', rawurlencode($gatewayReference), (string) config('payme.endpoints.verify'));
    }

    /**
     * Vérification brute (sans normalisation) : utilisée par le doctor pour
     * prouver que le jeton est accepté (404 TXN-002 sur une référence factice).
     */
    public function rawVerify(PaymentGateway $gateway, string $gatewayReference): Response
    {
        $url = $this->verifyUrl($gateway, $gatewayReference);

        return $this->authorized($gateway, fn (PendingRequest $http) => $http->post($url));
    }

    /**
     * Extrait le message d'erreur le plus utile d'une enveloppe PayMe.
     *
     * @param  array<string, mixed>  $data
     */
    public static function errorMessage(array $data): ?string
    {
        $fields = Arr::get($data, 'errors.errors');
        if (is_array($fields)) {
            $messages = [];
            foreach ($fields as $list) {
                $first = is_array($list) ? reset($list) : $list;
                if (is_string($first) && $first !== '') {
                    $messages[] = ucfirst($first);
                }
            }
            if ($messages) {
                return implode(' ', $messages);
            }
        }

        $errors = Arr::get($data, 'errors');
        if (is_array($errors) && ($flat = array_values(array_filter($errors, 'is_string')))) {
            return implode(' ', $flat);
        }

        foreach (['message', 'errors.message', 'data.message', 'error', 'detail', 'data'] as $path) {
            $value = Arr::get($data, $path);
            if (filled($value) && ! is_array($value)) {
                return (string) $value;
            }
        }

        $code = Arr::get($data, 'code');

        return is_string($code) && $code !== '' ? 'Erreur PayMe ('.$code.')' : null;
    }

    /**
     * Mappe un `data.status` PayMe (« Payment in progress », « Successful »…)
     * vers `success` | `failed` | `pending`.
     */
    public static function mapStatus(?string $status): string
    {
        $s = strtoupper(str_replace([' ', '-'], '_', trim((string) $status)));

        return match (true) {
            in_array($s, ['SUCCESSFUL', 'SUCCESS', 'SUCCEEDED', 'COMPLETED', 'PAID'], true) => 'success',
            in_array($s, ['FAILED', 'FAILURE', 'ERROR', 'REJECTED', 'DECLINED', 'CANCELLED', 'CANCELED', 'EXPIRED', 'TIMEOUT', 'REFUSED', 'INSUFFICIENT_FUNDS'], true) => 'failed',
            default => 'pending', // Initiated, Payment in progress, Pending, Awaiting validation
        };
    }

    /**
     * Envoie une requête avec le jeton ; sur 401, oublie le jeton et renvoie une seule fois.
     *
     * @param  callable(PendingRequest): Response  $request
     */
    private function authorized(PaymentGateway $gateway, callable $request): Response
    {
        $response = $request($this->http()->withToken($this->token($gateway)));

        if ($response->status() === 401) {
            $this->forgetToken($gateway);

            $response = $request($this->http()->withToken($this->token($gateway)));
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('payme.timeout', 20))
            ->connectTimeout(10);
    }

    /**
     * @return array{state: string, gateway_reference: string|null, gateway_status: string|null, provider: string|null, receipt_url: string|null, message: string|null, raw: array<string, mixed>}
     */
    private function normalize(Response $response): array
    {
        $data = (array) $response->json();
        $reference = Arr::get($data, 'data.gateway_reference') ?? Arr::get($data, 'gateway_reference');
        $status = Arr::get($data, 'data.status');
        $status = is_numeric($status) ? null : $status;
        $message = self::errorMessage($data);
        $provider = Arr::get($data, 'data.payment_provider_slug');
        $receipt = Arr::get($data, 'data.receipt.receipt_url');

        $base = [
            'gateway_reference' => $reference !== null ? (string) $reference : null,
            'gateway_status' => $status !== null ? (string) $status : null,
            'provider' => $provider ? strtoupper((string) $provider) : null,
            'receipt_url' => is_string($receipt) && str_starts_with($receipt, 'https://') ? $receipt : null,
            'message' => $message,
            'raw' => $data,
        ];

        // 409 à la vérification : transaction toujours en cours, pas un refus.
        if ($response->status() === 409) {
            return $base + ['state' => 'pending'];
        }

        // Erreur métier (422, 400…) ou refus en HTTP 200 avec success: false.
        if (($response->clientError() && $response->status() !== 404)
            || ($response->successful() && Arr::get($data, 'success') === false)) {
            return array_merge($base, ['state' => 'failed', 'message' => $message ?? 'Paiement refusé par PayMe.']);
        }

        // 404 (TXN-002 : référence inconnue), 5xx : issue incertaine.
        if (! $response->successful()) {
            throw new \RuntimeException('PayMe a répondu '.$response->status().($message ? ' : '.$message : ''));
        }

        return $base + ['state' => self::mapStatus($status)];
    }

    private function tokenKey(PaymentGateway $gateway): string
    {
        return 'payme_token:'.$gateway->id.':'.sha1($this->baseUrl($gateway).'|'.$gateway->user_name);
    }
}
