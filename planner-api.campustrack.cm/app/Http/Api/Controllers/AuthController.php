<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Support\AuthPayload;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use HttpResponses;

    public function register(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department_id' => $request->department_id,
            'requested_role' => $request->requested_role,
            // Le compte doit être validé par un administrateur avant connexion
            'is_approved' => false,
        ]);

        return $this->success(
            ['user' => $user],
            __('api.auth.registered'),
            201
        );
    }

    public function login(LoginUserRequest $request): JsonResponse
    {

        if (Auth::guard('web')->attempt($request->only('email', 'password'))) {
            $user = User::where('email', $request->email)->first();

            if (! $user->is_approved) {
                Auth::guard('web')->logout();

                return $this->error(
                    null,
                    __('api.auth.account_pending'),
                    403
                );
            }

            // Mode SPA Sanctum : la session (cookie httpOnly) fait foi,
            // aucun token n'est délivré au client.
            $request->session()->regenerate();

            return $this->success($this->authUserPayload($user), __('api.auth.signed_in'), 200);
        }

        return $this->error(null, __('api.auth.invalid_credentials'), 401);
    }

    public function logout(Request $request): JsonResponse
    {
        // Fin de la session SPA : le cookie de session est invalidé et
        // remplacé, l'accès API est donc coupé pour le front.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, __('api.auth.signed_out'), 200);
    }

    public function authUser(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->error(null, __('api.auth.not_authenticated'), 401);
        }

        return $this->success($this->authUserPayload($user), __('api.auth.authenticated'), 200);
    }

    /**
     * Serialise l'utilisateur authentifié avec ses rôles, ses permissions
     * effectives, les fonctionnalités de son tenant et son abonnement courant.
     */
    private function authUserPayload(User $user): User
    {
        return AuthPayload::for($user);
    }
}
