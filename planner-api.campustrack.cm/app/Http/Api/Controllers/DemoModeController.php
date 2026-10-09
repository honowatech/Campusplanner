<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuthPayload;
use App\Support\TenantContext;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoModeController extends Controller
{
    use HttpResponses;

    /**
     * Rôles proposés en mode démo (tous les rôles sauf `super-admin`).
     */
    private const DEMO_ROLES = [
        'administrateur',
        'responsable-departement',
        'personnel-administratif',
        'professeur',
        'etudiant',
    ];

    /**
     * État du mode démo + comptes disponibles (uniquement si activé).
     *
     * Public : la page de connexion (non authentifiée) doit pouvoir l'interroger.
     * Seul le tenant démo est concerné par le mode démo.
     */
    public function index(): JsonResponse
    {
        $demo = Tenant::demo();
        $enabled = $demo?->demoModeEnabled() ?? false;

        return $this->success(
            [
                'enabled' => $enabled,
                'accounts' => $enabled ? $this->demoAccounts() : [],
            ],
            'État du mode démo récupéré'
        );
    }

    /**
     * Active / désactive le mode démo du tenant démo. Réservé au super-admin.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'demo_mode' => ['required', 'boolean'],
        ]);

        $demo = Tenant::demo();

        abort_if(! $demo, 404, 'Aucun tenant démo.');

        $demo->update(['demo_mode' => $validated['demo_mode']]);

        return $this->success(
            [
                'enabled' => $demo->demo_mode,
                'accounts' => $demo->demo_mode ? $this->demoAccounts() : [],
            ],
            $demo->demo_mode ? 'Mode démo activé' : 'Mode démo désactivé'
        );
    }

    /**
     * Connexion démo sans mot de passe.
     *
     * Ne fonctionne que si le mode démo du tenant démo est actif, et ne cible
     * jamais le rôle `super-admin` ni un compte hors tenant démo.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', self::DEMO_ROLES)],
        ]);

        $demo = Tenant::demo();

        if (! $demo || ! $demo->demoModeEnabled()) {
            return $this->error(null, 'Le mode démo est désactivé.', 403);
        }

        // Le rôle `role()` est scopé au tenant démo : on résout dans ce périmètre.
        $user = TenantContext::run($demo->id, fn () => User::where('is_demo', true)
            ->where('tenant_id', $demo->id)
            ->where('is_approved', true)
            ->role($validated['role'])
            ->first());

        if (! $user) {
            return $this->error(null, 'Aucun compte démo disponible pour ce rôle.', 404);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $this->success(AuthPayload::for($user), 'Connecté en mode démo');
    }

    /**
     * Liste des comptes démo (un par rôle), dans un ordre stable.
     */
    private function demoAccounts(): array
    {
        $demo = Tenant::demo();

        if (! $demo) {
            return [];
        }

        $order = array_flip(self::DEMO_ROLES);

        // Les rôles des comptes démo sont scopés au tenant démo : on résout donc
        // `hasAnyRole`/`getRoleNames` dans ce périmètre (la requête est publique,
        // aucun contexte tenant n'est posé par ResolveTenant).
        return TenantContext::run($demo->id, function () use ($demo, $order) {
            return User::where('is_demo', true)
                ->where('tenant_id', $demo->id)
                ->where('is_approved', true)
                ->get()
                ->filter(fn (User $user) => $user->hasAnyRole(self::DEMO_ROLES))
                ->map(fn (User $user) => [
                    'role' => $user->getRoleNames()->first(),
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->sortBy(fn (array $account) => $order[$account['role']] ?? 99)
                ->values()
                ->all();
        });
    }
}
