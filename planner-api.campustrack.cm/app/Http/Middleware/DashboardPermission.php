<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DashboardPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // La route applique déjà role:super-admin|administrateur ; on borne ici
        // simplement l'administrateur à son propre département.
        if ($user->hasRole('administrateur') && ! $user->hasRole('super-admin')) {
            $requestedDeptId = $request->get('department_id');

            if ($requestedDeptId && $requestedDeptId != $user->department_id) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Accès non autorisé. Vous ne pouvez voir que les données de votre département',
                    'data' => null,
                ], 403);
            }
        }

        return $next($request);
    }
}
