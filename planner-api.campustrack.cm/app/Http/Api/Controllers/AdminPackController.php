<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Pack;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion des packs d'abonnement (super-admin).
 */
class AdminPackController extends Controller
{
    use HttpResponses;

    public function index(): JsonResponse
    {
        $packs = Pack::query()
            ->with('features:id,key,label')
            ->orderBy('sort')
            ->get();

        return $this->success(['packs' => $packs], 'Liste des packs récupérée');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePack($request);

        $pack = Pack::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'tier' => $validated['tier'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'XAF',
            'billing_period' => $validated['billing_period'],
            'is_active' => $validated['is_active'] ?? true,
            'sort' => $validated['sort'] ?? 0,
        ]);

        $this->syncFeatures($pack, $validated['features'] ?? []);

        return $this->success(['pack' => $pack->load('features')], 'Pack créé', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pack = Pack::findOrFail($id);
        $validated = $this->validatePack($request, $id);

        $pack->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'tier' => $validated['tier'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'XAF',
            'billing_period' => $validated['billing_period'],
            'is_active' => $validated['is_active'] ?? true,
            'sort' => $validated['sort'] ?? 0,
        ]);

        if (array_key_exists('features', $validated)) {
            $this->syncFeatures($pack, $validated['features']);
        }

        return $this->success(['pack' => $pack->load('features')], 'Pack mis à jour');
    }

    public function destroy(int $id): JsonResponse
    {
        $pack = Pack::findOrFail($id);

        if ($pack->subscriptions()->exists()) {
            return $this->error(null, 'Ce pack est utilisé par des abonnements.', 400);
        }

        $pack->delete();

        return $this->success(null, 'Pack supprimé');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePack(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:packs,slug'.($id ? ','.$id : ''),
            'tier' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|max:3',
            'billing_period' => 'required|in:monthly,annual',
            'is_active' => 'sometimes|boolean',
            'sort' => 'sometimes|integer',
            'features' => 'sometimes|array',
            'features.*' => 'exists:features,key',
        ]);
    }

    /**
     * @param  list<string>  $featureKeys
     */
    private function syncFeatures(Pack $pack, array $featureKeys): void
    {
        $featureIds = Feature::whereIn('key', $featureKeys)->pluck('id');
        $pack->features()->sync($featureIds);
    }
}
