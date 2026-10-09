<?php

namespace App\Support;

use App\Models\User;
use App\Services\TenantSubscriptionsService;

/**
 * Construit le payload sérialisé de l'utilisateur authentifié (rôles,
 * permissions effectives, fonctionnalités du tenant et abonnement courant),
 * consommé par /auth-user et /demo-login.
 */
class AuthPayload
{
    public static function for(User $user): User
    {
        // Résout rôles/permissions dans le bon périmètre tenant. Sans cela, lors
        // du login/demo-login (où ResolveTenant n'a pas encore posé le contexte,
        // la requête étant publique), les rôles et permissions seraient résolus
        // en contexte global (team NULL) et donc vides.
        return TenantContext::run($user->tenant_id, function () use ($user) {
            $user->load('roles');
            $user->setRelation('permissions', $user->getAllPermissions());
            $user->setRelation('features', FeatureRegistry::enabledForUser($user));

            $tenant = $user->tenant;

            $user->setRelation('subscription', $tenant
                ? app(TenantSubscriptionsService::class)->currentSummary($tenant)
                : null);

            return $user;
        });
    }
}
