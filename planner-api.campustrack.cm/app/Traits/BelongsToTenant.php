<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait à appliquer aux modèles « appartenant à un tenant ».
 *
 * - Enregistre le TenantScope (isolation automatique des lectures).
 * - Renseigne automatiquement tenant_id à la création, depuis le contexte courant.
 * - Expose la relation tenant() et un scope inTenant().
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id === null && TenantContext::currentId() !== null) {
                $model->tenant_id = TenantContext::currentId();
            }
        });
    }

    /**
     * The tenant that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Restreint explicitement au tenant donné.
     */
    public function scopeInTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($this->getTable().'.tenant_id', $tenantId);
    }
}
