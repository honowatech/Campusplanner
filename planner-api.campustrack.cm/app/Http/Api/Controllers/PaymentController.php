<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use HttpResponses;

    /**
     * Initie un paiement d'abonnement (admin tenant).
     *
     * Idempotence : en-tête `Idempotency-Key` (rejeu → paiement existant).
     */
    public function checkout(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;

        abort_if(! $tenant, 403, 'Aucun tenant associé à ce compte.');

        $validated = $request->validate([
            'pack_id' => 'required|integer|exists:packs,id',
            'phone' => 'required|string|max:20',
            'billing_period' => 'sometimes|in:monthly,annual',
        ]);

        $pack = Pack::query()->active()->findOrFail($validated['pack_id']);

        $payment = app(PaymentService::class)->checkout(
            $tenant,
            $pack,
            $validated['phone'],
            $validated['billing_period'] ?? null,
            $request->header('Idempotency-Key'),
        );

        return $this->success(['payment' => $payment], 'Paiement initié', 201);
    }

    /**
     * Historique des paiements du tenant.
     */
    public function payments(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;

        abort_if(! $tenant, 403, 'Aucun tenant associé à ce compte.');

        $payments = Payment::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(['payments' => $payments], 'Historique des paiements récupéré');
    }
}
