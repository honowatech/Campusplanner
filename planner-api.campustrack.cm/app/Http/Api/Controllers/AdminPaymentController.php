<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    use HttpResponses;

    /**
     * Vue agrégée des paiements (super-admin), tous tenants confondus.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with('tenant:id,name,slug')
            ->when($request->has('tenant_id'), fn ($q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(['payments' => $payments], 'Paiements récupérés');
    }
}
