<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque l'accès aux utilisateurs d'un tenant suspendu.
 *
 * Le super-admin (global, sans tenant) n'est pas concerné.
 */
class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant && $user->tenant->status === 'suspended') {
            abort(403, 'Votre compte est suspendu. Contactez votre administrateur.');
        }

        return $next($request);
    }
}
