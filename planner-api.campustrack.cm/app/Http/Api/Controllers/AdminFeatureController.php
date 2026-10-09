<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Support\FeatureRegistry;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion du catalogue de fonctionnalités (super-admin).
 */
class AdminFeatureController extends Controller
{
    use HttpResponses;

    public function index(): JsonResponse
    {
        $features = Feature::query()->orderBy('id')->get();

        return $this->success(['features' => $features], 'Liste des fonctionnalités récupérée');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $feature = Feature::findOrFail($id);

        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $feature->update($validated);

        // Invalide les caches de fonctionnalités des tenants concernés.
        FeatureRegistry::flushCacheForAll();

        return $this->success(['feature' => $feature], 'Fonctionnalité mise à jour');
    }
}
