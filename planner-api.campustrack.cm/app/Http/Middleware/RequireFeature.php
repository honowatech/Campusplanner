<?php

namespace App\Http\Middleware;

use App\Support\FeatureRegistry;
use App\Support\RoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde de fonctionnalité (`feature:key`) : refuse l'accès si le tenant de
 * l'utilisateur ne dispose pas de la fonctionnalité dans son pack.
 *
 * - Super-admin (global) : accès sans restriction.
 * - Tenant démo : accès à toutes les fonctionnalités.
 * - Autre tenant : accès selon son abonnement courant.
 */
class RequireFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, __('api.errors.unauthenticated'));
        }

        if ($user->hasRole(RoleCatalog::GLOBAL_ROLE)) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant || ! FeatureRegistry::has($tenant, $feature)) {
            abort(403, __('api.errors.feature_not_included'));
        }

        return $next($request);
    }
}
