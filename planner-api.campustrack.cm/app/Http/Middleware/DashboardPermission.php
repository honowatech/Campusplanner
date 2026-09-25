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

        // Check if user has required role
        if (! $user->hasAnyRole(['super-admin', 'administrateur'])) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Rôle requis: super-admin ou administrateur',
                'data' => null,
            ], 403);
        }

        // For admin, ensure they can't access other departments
        if ($user->hasRole('administrateur') && ! $user->hasRole('super-admin')) {
            $requestedDeptId = $request->get('department_id');

            if ($requestedDeptId && $requestedDeptId != $user->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Vous ne pouvez voir que les données de votre département',
                    'data' => null,
                ], 403);
            }
        }

        return $next($request);
    }
}
