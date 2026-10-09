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

        return $this->success(['gateway' => $this->payload($gateway)], 'Passerelle récupérée');
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'password' => 'sometimes|nullable|string|max:255',
            'app_id' => 'sometimes|nullable|string|max:255',
            'endpoint' => 'sometimes|nullable|string|max:255',
            'pay_type_id' => 'sometimes|nullable|string|max:255',
            'client_fees_rate' => 'sometimes|numeric|min:0|max:1',
            'mode' => 'sometimes|in:sandbox,live',
            'is_active' => 'sometimes|boolean',
        ]);

        $gateway = PaymentGateway::query()->firstOrNew(['provider' => 'payme']);

        foreach (['user_name', 'app_id', 'endpoint', 'pay_type_id', 'client_fees_rate', 'mode', 'is_active'] as $field) {
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
            'mode' => $gateway->mode,
            'is_active' => (bool) $gateway->is_active,
        ]);

        return $this->success(['gateway' => $this->payload($gateway)], 'Passerelle mise à jour');
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
            'app_id' => $gateway->app_id,
            'endpoint' => $gateway->endpoint,
            'pay_type_id' => $gateway->pay_type_id,
            'client_fees_rate' => $gateway->client_fees_rate,
            'mode' => $gateway->mode,
            'is_active' => $gateway->is_active,
        ];
    }
}
