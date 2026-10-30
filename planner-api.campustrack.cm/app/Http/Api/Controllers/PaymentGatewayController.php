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
                'sometimes', 'nullable', 'url:https', 'max:255',
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

        foreach (['user_name', 'endpoint', 'client_fees_rate', 'timeout_minutes', 'is_active'] as $field) {
            if (array_key_exists($field, $validated)) {
                $gateway->{$field} = $validated[$field];
            }
        }

        if (! empty($validated['password'])) {
            $gateway->password = $validated['password'];
        }

        $gateway->save();

        // Invalide le JWT en cache (les crédences ont pu changer).
        app(PaymeClient::class)->forgetToken($gateway);

        AuditService::record('payment_gateway.updated', $gateway, [], [
            'user_name' => $gateway->user_name,
            'endpoint' => $gateway->endpoint,
            'is_active' => (bool) $gateway->is_active,
        ]);

        return $this->success(['gateway' => $this->payload($gateway)], __('admin.payment_gateway.updated'));
    }

    /**
     * Teste la connexion PayMe avec les identifiants enregistrés (login, sans
     * rien débiter) et renvoie l'URL réellement appelée.
     */
    public function test(): JsonResponse
    {
        $gateway = PaymentGateway::active() ?? PaymentGateway::query()->latest()->first();

        if (! $gateway) {
            return $this->error(['ok' => false, 'status' => null, 'message' => __('admin.payment_gateway.missing'), 'url' => null], __('admin.payment_gateway.missing'), 404);
        }

        $result = app(PaymeClient::class)->testConnection($gateway);

        return $this->success($result, $result['message']);
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
            'endpoint' => $gateway->endpoint,
            'default_endpoint' => config('payme.base_url'),
            'client_fees_rate' => $gateway->client_fees_rate,
            'timeout_minutes' => $gateway->timeoutMinutes(),
            'has_password' => filled($gateway->password),
            'is_active' => $gateway->is_active,
        ];
    }
}
