<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\AuditService;
use App\Services\PaymeClient;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Configuration PayMe (super-admin).
 */
class PaymentGatewayController extends Controller
{
    use HttpResponses;

    public function show(): JsonResponse
    {
        $gateway = PaymentGateway::active() ?? PaymentGateway::query()->latest()->first();

        return $this->success(['gateway' => $this->payload($gateway)], __('admin.payment_gateway.retrieved'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'password' => 'sometimes|nullable|string|max:255',
            'endpoint' => [
                'sometimes', 'nullable', 'string', 'max:255', 'url:https',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && PaymeClient::isInvalidBaseUrl($value)) {
                        $fail(__('admin.payment_gateway.invalid_endpoint'));
                    }
                },
            ],
            'client_fees_rate' => 'sometimes|nullable|integer|min:0|max:100',
            'timeout_minutes' => 'sometimes|integer|min:2|max:60',
            'is_active' => 'sometimes|boolean',
        ]);

        $gateway = PaymentGateway::query()->firstOrNew(['provider' => 'payme']);

        foreach (['user_name', 'client_fees_rate', 'timeout_minutes', 'is_active'] as $field) {
            if (array_key_exists($field, $validated)) {
                $gateway->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('endpoint', $validated)) {
            $endpoint = trim((string) $validated['endpoint']);
            $gateway->endpoint = $endpoint === '' ? null : rtrim($endpoint, '/');
        }

        if (! empty($validated['password'])) {
            $gateway->password = $validated['password'];
        }

        $gateway->save();

        // Invalide le jeton en cache (les crédences ont pu changer).
        app(PaymeClient::class)->forgetToken($gateway);

        AuditService::record('payment_gateway.updated', $gateway, [], [
            'user_name' => $gateway->user_name,
            'endpoint' => $gateway->endpoint,
            'client_fees_rate' => $gateway->client_fees_rate,
            'timeout_minutes' => $gateway->timeout_minutes,
            'is_active' => (bool) $gateway->is_active,
        ]);

        return $this->success(['gateway' => $this->payload($gateway)], __('admin.payment_gateway.updated'));
    }

    /**
     * Teste la connexion avec les identifiants enregistrés : répond toujours
     * avec le code HTTP, le message complet et l'URL réellement appelée.
     */
    public function test(): JsonResponse
    {
        $gateway = PaymentGateway::query()->where('provider', 'payme')->latest()->first();

        if (! $gateway || blank($gateway->user_name) || blank($gateway->password)) {
            return $this->success([
                'ok' => false,
                'status' => 0,
                'message' => __('admin.payment_gateway.not_configured'),
                'url' => null,
            ], __('admin.payment_gateway.test_failed'));
        }

        $result = app(PaymeClient::class)->testConnection($gateway);

        AuditService::record('payment_gateway.tested', $gateway, [], [
            'ok' => $result['ok'],
            'status' => $result['status'],
            'url' => $result['url'],
        ]);

        return $this->success([
            'ok' => $result['ok'],
            'status' => $result['status'],
            'message' => $result['message'],
            'url' => $result['url'],
            'effective_url' => $result['effective_url'],
            'expires_at' => $result['expires_at'],
        ], $result['ok'] ? __('admin.payment_gateway.test_ok') : __('admin.payment_gateway.test_failed'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payload(?PaymentGateway $gateway): ?array
    {
        if (! $gateway) {
            return null;
        }

        return [
            'id' => $gateway->id,
            'provider' => $gateway->provider,
            'user_name' => $gateway->user_name,
            'has_password' => filled($gateway->password),
            'endpoint' => $gateway->endpoint,
            'default_endpoint' => rtrim((string) config('payme.base_url'), '/'),
            'client_fees_rate' => $gateway->client_fees_rate,
            'timeout_minutes' => $gateway->timeout_minutes ?: (int) config('payme.timeout_minutes', 15),
            'is_active' => $gateway->is_active,
        ];
    }
}
