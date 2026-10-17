<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\PaymentService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paiement d'abonnement en libre-service (admin tenant) : l'école choisit une
 * formule, valide sur son téléphone, et la page suit la confirmation jusqu'à
 * l'activation de l'abonnement.
 */
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
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'pack_id' => 'required|integer|exists:packs,id',
            'phone' => 'required|string|max:20',
            'billing_period' => 'sometimes|in:monthly,annual',
        ]);

        $pack = Pack::query()->active()->findOrFail($validated['pack_id']);

        try {
            $payment = app(PaymentService::class)->checkout(
                $tenant,
                $pack,
                $validated['phone'],
                $validated['billing_period'] ?? null,
                $request->header('Idempotency-Key'),
            );
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 422);
        }

        return $this->success(['payment' => $payment], __('admin.payments.initiated'), 201);
    }

    /**
     * État d'un paiement, vérifié en direct auprès de PayMe (polling de la page).
     */
    public function show(Request $request, int $payment): JsonResponse
    {
        $model = $this->ownedPayment($request, $payment);

        $model = app(PaymentService::class)->verify($model);

        return $this->success(['payment' => $model], __('admin.payments.retrieved'));
    }

    /**
     * Le client renonce à attendre la validation sur son téléphone.
     */
    public function cancel(Request $request, int $payment): JsonResponse
    {
        $model = $this->ownedPayment($request, $payment);

        $model = app(PaymentService::class)->cancel($model);

        return $this->success(['payment' => $model], __('admin.payments.canceled'));
    }

    /**
     * Historique des paiements du tenant.
     */
    public function payments(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $payments = Payment::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(['payments' => $payments], __('admin.payments.history_retrieved'));
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->user()?->tenant;

        abort_if(! $tenant, 403, __('admin.tenant.not_found'));

        return $tenant;
    }

    private function ownedPayment(Request $request, int $id): Payment
    {
        return Payment::query()
            ->where('tenant_id', $this->tenant($request)->id)
            ->findOrFail($id);
    }
}
