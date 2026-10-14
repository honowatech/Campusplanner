<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le tenant courant depuis l'utilisateur authentifié et l'expose via
 * TenantContext. Le tenant_id est TOUJOURS issu de la session (jamais d'un
 * paramètre client) : c'est le socle de l'isolation multitenant.
 *
 * Les routes publiques (login, register, …) n'ont pas d'utilisateur : on ne
 * touche pas au contexte. Le contexte est restauré après les requêtes
 * authentifiées (try/finally) pour éviter toute fuite entre requêtes d'un
 * processus de longue durée (tests, Octane).
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return $next($request);
        }

        $previous = TenantContext::currentId();

        // Met à jour TenantContext (et le team spatie, via TenantTeamResolver).
        TenantContext::set($user->tenant_id);

        try {
            return $next($request);
        } finally {
            TenantContext::set($previous);
        }
    }
}
