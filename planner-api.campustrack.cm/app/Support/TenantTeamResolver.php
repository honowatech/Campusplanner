<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Résolveur de « team » spatie qui délègue à TenantContext.
 *
 * Le DefaultTeamResolver de spatie stocke l'id dans une propriété d'instance du
 * singleton PermissionRegistrar ; celui-ci est recréé à chaque refreshApplication
 * (tests) et perd donc l'état. TenantContext, au contraire, repose sur des
 * propriétés statiques : le contexte de tenant survit au cycle de vie de l'app.
 */
class TenantTeamResolver implements PermissionsTeamResolver
{
    public function setPermissionsTeamId($id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        TenantContext::set($id);
    }

    public function getPermissionsTeamId(): int|string|null
    {
        return TenantContext::currentId();
    }
}
