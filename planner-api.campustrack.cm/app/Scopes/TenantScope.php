<?php

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope : restreint les requêtes Eloquent au tenant courant.
 *
 * En contexte global (super-admin / CLI sans tenant), aucun filtre n'est
 * appliqué : le super-admin voit l'ensemble des données de tous les tenants.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::currentId();

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
