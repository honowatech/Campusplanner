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

        abort_if(! $tenant, 403, __('admin.tenant.not_found'));

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

        return $this->success(['payment' => $payment], __('admin.payments.initiated'), 201);
    }

    /**
     * Le client renonce à attendre la confirmation (admin tenant, propriétaire
     * du paiement uniquement). Le paiement reste revérifié une heure : l'invite
     * peut encore être validée sur le téléphone.
     */
    public function cancel(Request $request, Payment $payment): JsonResponse
    {
        $tenant = $request->user()?->tenant;

        abort_if(! $tenant, 403, __('admin.tenant.not_found'));
        abort_if($payment->tenant_id !== $tenant->id, 404, __('admin.payments.not_found'));

        if (! in_array($payment->status, ['pending', 'in_progress'], true)) {
            return $this->error(['payment' => $payment], __('admin.payments.cannot_cancel'), 422);
        }

        $payment = app(PaymentService::class)->cancel($payment);

        return $this->success(['payment' => $payment], __('admin.payments.cancelled'));
    }

    /**
     * Historique des paiements du tenant.
     */
    public function payments(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;

        abort_if(! $tenant, 403, __('admin.tenant.not_found'));

        $payments = Payment::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(['payments' => $payments], __('admin.payments.history_retrieved'));
    }
}
