<?php

namespace App\Http\Middleware;

use App\Traits\HttpResponses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    use HttpResponses;

    /**
     * Un compte en attente de validation ou suspendu perd l'accès à l'API
     * même si sa session cookie est encore valide.
     *
     * Les tokens Bearer n'existent plus : sans cette vérification, une session
     * ouverte avant une suspension resterait utilisable jusqu'à l'expiration
     * de la session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();

        if ($user && ! $user->is_approved) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $this->error(
                null,
                'Votre compte est en attente de validation ou a été suspendu par un administrateur.',
                403
            );
        }

        return $next($request);
    }
}
