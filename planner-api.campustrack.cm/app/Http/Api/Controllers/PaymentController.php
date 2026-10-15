<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\HttpResponses;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        try {
            $payment = app(PaymentService::class)->checkout(
                $tenant,
                $pack,
                $validated['phone'],
                $validated['billing_period'] ?? null,
                $request->header('Idempotency-Key'),
            );
        } catch (\RuntimeException $e) {
            // Passerelle absente/inactive, tenant démo, adresse PayMe invalide :
            // une erreur de configuration explicite, pas une 500 anonyme.
            return $this->error(null, $e->getMessage(), 422);
        } catch (QueryException $e) {
            Log::error('PayMe : erreur SQL au checkout (migration manquante ?).', ['error' => $e->getMessage()]);

            return $this->error(null, __('admin.payments.storage_error'), 500);
        }

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
