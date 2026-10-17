<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client de l'API Partenaire PayMe (MamoniPay).
 *
 * Contrat réel : docs/FEUILLE_DE_ROUTE_PAYME.md § 0. Les réponses d'initiation
 * et de vérification sont normalisées en
 * `['state' => 'pending'|'success'|'failed', 'gateway_reference', 'gateway_status', 'provider', 'receipt_url', 'message', 'raw']`.
 */
class PaymeClient
{
    /**
     * Retourne le jeton d'accès (mis en cache jusqu'à peu avant `expires_at`).
     */
    public function token(PaymentGateway $gateway): string
    {
        $key = $this->tokenKey($gateway);

        if ($token = Cache::get($key)) {
            return $token;
        }

        $url = $this->baseUrl($gateway).config('payme.endpoints.login');
        $response = $this->http()->post($url, [
            'login' => $gateway->user_name,
            'password' => $gateway->password,
        ]);

        $data = (array) $response->json();
        $token = Arr::get($data, 'data.access_token') ?? Arr::get($data, 'access_token');

        if (! $response->successful() || Arr::get($data, 'success') === false || ! $token) {
            $effective = (string) $response->effectiveUri();

            throw new \RuntimeException(sprintf(
                'Connexion PayMe refusée (%d)%s — URL appelée : %s%s',
                $response->status(),
                ($message = self::errorMessage($data)) ? ' : '.$message : '',
                $url,
                $effective !== '' && $effective !== $url ? ' (redirigée vers '.$effective.')' : '',
            ));
        }

        $ttl = 3000;
        if ($expiresAt = Arr::get($data, 'data.expires_at')) {
            try {
                $ttl = (int) now()->diffInSeconds(CarbonImmutable::parse($expiresAt), false);
            } catch (\Throwable) {
                // expires_at illisible : TTL par défaut.
            }
        }

        Cache::put($key, (string) $token, max(60, $ttl - 60));

        return (string) $token;
    }

    /**
     * Oublie le jeton en cache (régénération forcée, ex. identifiants modifiés).
     */
    public function forgetToken(PaymentGateway $gateway): void
    {
        Cache::forget($this->tokenKey($gateway));
    }

    /**
     * Teste la connexion (login avec jeton vidé) sans rien débiter.
     *
     * @return array{ok: bool, status: int|null, message: string, url: string|null}
     */
    public function testConnection(PaymentGateway $gateway): array
    {
        $this->forgetToken($gateway);

        try {
            $url = $this->baseUrl($gateway).config('payme.endpoints.login');
            $this->token($gateway);

            return ['ok' => true, 'status' => 200, 'message' => 'Connexion PayMe réussie.', 'url' => $url];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => null, 'message' => $e->getMessage(), 'url' => $url ?? null];
        }
    }

    /**
     * Normalise un numéro de téléphone au format international attendu par
     * PayMe : chiffres uniquement, indicatif pays `237` préfixé si absent.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);
        $digits = (string) preg_replace('/^00/', '', $digits);

        // Numéro local camerounais (9 chiffres) → préfixe « 237 ».
        if (strlen($digits) === 9) {
            $digits = '237'.$digits;
        }

        return $digits;
    }

    /**
     * Initie un paiement mobile money.
     *
     * @param  array{amount: int|float, phone_number: string, external_reference: string, client_name?: string|null, client_fees_rate?: int|null}  $payload
     * @return array<string, mixed>
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

        return $this->normalize($this->authorized($gateway, fn (PendingRequest $http) => $http->post($url, $body)));
    }

    /**
     * Vérifie le statut d'une transaction par sa `gateway_reference`.
     *
     * @return array<string, mixed>
     */
    public function paymentStatus(PaymentGateway $gateway, ?string $gatewayReference): array
    {
        if (blank($gatewayReference)) {
            throw new \RuntimeException('Aucune gateway_reference : la demande n\'a pas été initiée chez PayMe.');
        }

        $url = $this->baseUrl($gateway).str_replace(
            '{reference}',
            rawurlencode((string) $gatewayReference),
            (string) config('payme.endpoints.verify')
        );

        return $this->normalize($this->authorized($gateway, fn (PendingRequest $http) => $http->post($url)));
    }

    /**
     * Mappe un statut PayMe (`data.status`) vers `success` | `failed` | `pending`.
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
     * Extrait un message d'erreur lisible d'une réponse PayMe.
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
     * Adresse de base de l'API, refusée si elle pointe vers la documentation
     * ou se termine par une version (`/v1`).
     */
    public function baseUrl(PaymentGateway $gateway): string
    {
        $base = rtrim((string) ($gateway->endpoint ?: config('payme.base_url')), '/');

        if (self::isInvalidBaseUrl($base)) {
            throw new \RuntimeException("Adresse PayMe invalide ({$base}) : indiquer l'adresse de base de l'API, ex. https://console-v2.mamonipay.me/api");
        }

        return $base;
    }

    /**
     * Indique si l'adresse de base est manifestement erronée.
     */
    public static function isInvalidBaseUrl(string $base): bool
    {
        return (bool) preg_match('#/docs(/|$)|/v\d+$#i', rtrim($base, '/'));
    }

    /**
     * Normalise une réponse d'initiation ou de vérification.
     *
     * @return array<string, mixed>
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

        // Transaction toujours en cours : pas un refus.
        if ($response->status() === 409) {
            return ['state' => 'pending'] + $base;
        }

        if (($response->clientError() && $response->status() !== 404)
            || ($response->successful() && Arr::get($data, 'success') === false)) {
            return ['state' => 'failed', 'message' => $message ?? 'Paiement refusé par PayMe.'] + $base;
        }

        // 404 TXN-002, 5xx : issue incertaine.
        if (! $response->successful()) {
            throw new \RuntimeException('PayMe a répondu '.$response->status().($message ? ' : '.$message : ''));
        }

        return ['state' => self::mapStatus($status)] + $base;
    }

    /**
     * Envoie une requête authentifiée ; sur 401, oublie le jeton et renvoie une fois.
     *
     * @param  callable(PendingRequest): Response  $send
     */
    private function authorized(PaymentGateway $gateway, callable $send): Response
    {
        $response = $send($this->http()->withToken($this->token($gateway)));

        if ($response->status() === 401) {
            $this->forgetToken($gateway);
            $response = $send($this->http()->withToken($this->token($gateway)));
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

    private function tokenKey(PaymentGateway $gateway): string
    {
        $base = rtrim((string) ($gateway->endpoint ?: config('payme.base_url')), '/');

        return 'payme_token:'.$gateway->id.':'.sha1($base.'|'.$gateway->user_name);
    }
}
