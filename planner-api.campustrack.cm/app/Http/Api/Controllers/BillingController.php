<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Services\TenantSubscriptionsService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use HttpResponses;

    /**
     * Packs actifs (pour la page « Tarifs »).
     */
    public function packs(): JsonResponse
    {
        $packs = Pack::query()
            ->active()
            ->orderBy('sort')
            ->get()
            ->map(fn (Pack $pack) => [
                'id' => $pack->id,
                'name' => $pack->name,
                'tier' => $pack->tier,
                'slug' => $pack->slug,
                'price' => (float) $pack->price,
                'currency' => $pack->currency,
                'billing_period' => $pack->billing_period,
                'description' => $pack->description,
                'features' => $pack->features()->where('is_active', true)->pluck('key')->all(),
            ])
            ->values();

        return $this->success(['packs' => $packs], 'Liste des packs récupérée');
    }

    /**
     * Abonnement courant du tenant de l'utilisateur.
     */
    public function subscription(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;

        $subscription = $tenant
            ? app(TenantSubscriptionsService::class)->currentSummary($tenant)
            : null;

        return $this->success(['subscription' => $subscription], 'Abonnement courant récupéré');
    }
}
