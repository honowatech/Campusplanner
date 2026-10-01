<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
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
            'Compte créé avec succès. En attente de validation par un administrateur.',
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
                    'Votre compte est en attente de validation par un administrateur.',
                    403
                );
            }

            // Mode SPA Sanctum : la session (cookie httpOnly) fait foi,
            // aucun token n'est délivré au client.
            $request->session()->regenerate();

            return $this->success($this->authUserPayload($user), 'User signed in successfully', 200);
        }

        return $this->error(null, 'Invalid credentials', 401);
    }

    public function logout(Request $request): JsonResponse
    {
        // Fin de la session SPA : le cookie de session est invalidé et
        // remplacé, l'accès API est donc coupé pour le front.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'User signed out successfully', 200);
    }

    public function authUser(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->error(null, 'User not authenticated', 401);
        }

        return $this->success($this->authUserPayload($user), 'User is authenticated', 200);
    }

    /**
     * Serialise l'utilisateur authentifié avec ses rôles et ses permissions
     * effectives (directes + héritées des rôles), nécessaires au front pour
     * déterminer le rôle primaire et les gardes d'accès.
     */
    private function authUserPayload(User $user): User
    {
        $user->load('roles');
        $user->setRelation('permissions', $user->getAllPermissions());

        return $user;
    }
}
