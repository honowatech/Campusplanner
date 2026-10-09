<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthPayload;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Impersonation d'un tenant par le super-admin.
 */
class AdminImpersonationController extends Controller
{
    use HttpResponses;

    /**
     * Démarre une impersonation : se connecte comme l'utilisateur cible.
     */
    public function impersonate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $target = User::findOrFail($validated['user_id']);

        $request->session()->put('impersonator_id', $request->user()->id);

        Auth::guard('web')->login($target);
        $request->session()->regenerate();

        return $this->success(AuthPayload::for($target), 'Impersonation démarrée');
    }

    /**
     * Termine l'impersonation et revient au compte super-admin d'origine.
     */
    public function stop(Request $request): JsonResponse
    {
        $adminId = $request->session()->pull('impersonator_id');

        if (! $adminId) {
            return $this->error(null, 'Aucune impersonation active.', 400);
        }

        $admin = User::find($adminId);

        if (! $admin) {
            return $this->error(null, 'Compte administrateur introuvable.', 404);
        }

        Auth::guard('web')->login($admin);
        $request->session()->regenerate();

        return $this->success(AuthPayload::for($admin), 'Impersonation terminée');
    }
}
