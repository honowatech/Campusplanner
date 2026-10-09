<?php

namespace App\Http\Middleware;

use App\Support\RoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège le tenant démo des mutations destructives.
 *
 * Les utilisateurs du tenant démo peuvent explorer et créer librement, mais
 * les suppressions (DELETE) sont bloquées pour préserver l'intégrité du bac à
 * sable partagé. Le super-admin (global) n'est pas concerné.
 *
 * Le paiement réel est quant à lui bloqué au niveau service
 * (PaymentService::checkout).
 */
class ProtectDemoTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasRole(RoleCatalog::GLOBAL_ROLE)) {
            $tenant = $user->tenant;

            if ($tenant?->is_demo && $request->isMethod('delete')) {
                abort(403, 'Les suppressions sont désactivées sur le tenant démo.');
            }
        }

        return $next($request);
    }
}
