<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Support\FeatureRegistry;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    use HttpResponses;

    /**
     * Liste les fonctionnalités du catalogue avec leur état (`enabled`) pour
     * le tenant de l'utilisateur courant.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $enabled = $user ? array_flip(FeatureRegistry::enabledForUser($user)) : [];

        $features = FeatureRegistry::allActive()->map(fn ($feature) => [
            'key' => $feature->key,
            'label' => $feature->label,
            'group' => $feature->group,
            'description' => $feature->description,
            'enabled' => isset($enabled[$feature->key]),
        ])->values();

        return $this->success(
            [
                'features' => $features,
                'groups' => FeatureRegistry::groups(),
            ],
            'Liste des fonctionnalités récupérée'
        );
    }
}
