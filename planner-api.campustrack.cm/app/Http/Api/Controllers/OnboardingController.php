<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use App\Support\AuthPayload;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Onboarding public en libre-service : une école crée son espace (tenant +
 * compte admin initial approuvé d'office + essai gratuit) et se retrouve
 * immédiatement connectée, sans validation du super-admin. La formule choisie
 * sur la page d'inscription est mémorisée pour enchaîner sur le paiement.
 */
class OnboardingController extends Controller
{
    use HttpResponses;

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255|alpha_dash|unique:tenants,slug',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'phone' => 'sometimes|nullable|string|max:20',
            'pack_id' => 'sometimes|nullable|integer|exists:packs,id',
        ]);

        $pack = isset($validated['pack_id'])
            ? Pack::query()->active()->find($validated['pack_id'])
            : null;

        $tenant = app(TenantService::class)->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $this->uniqueSlug($validated['name']),
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'billing_phone' => $validated['phone'] ?? null,
            'settings' => $pack ? ['intended_pack_id' => $pack->id] : null,
        ]);

        // Connexion immédiate de l'admin (session SPA Sanctum).
        $admin = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['admin_email'])
            ->firstOrFail();

        Auth::guard('web')->login($admin);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->success([
            'tenant' => $tenant,
            'user' => AuthPayload::for($admin),
            'intended_pack_id' => $pack?->id,
        ], __('admin.onboarding.registered'), 201);
    }

    /**
     * Identifiant unique dérivé du nom de l'école (`ecole-x`, `ecole-x-2`…).
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'ecole';
        $slug = $base;
        $i = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
