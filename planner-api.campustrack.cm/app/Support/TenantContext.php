<?php

namespace App\Support;

use App\Models\Tenant;
use Closure;

/**
 * Contexte « tenant courant » de la requête.
 *
 * Résolu par le middleware ResolveTenant à partir de l'utilisateur authentifié,
 * puis consommé par le TenantScope et le trait BelongsToTenant.
 *
 * - Utilisateur d'un tenant : currentId() renvoie son tenant_id.
 * - Super-admin (global)  : currentId() renvoie null -> aucun filtre.
 */
class TenantContext
{
    protected static ?int $tenantId = null;

    protected static ?Tenant $tenant = null;

    /**
     * Définit l'identifiant du tenant courant (null = contexte global).
     */
    public static function set(?int $tenantId): void
    {
        static::$tenantId = $tenantId;
        static::$tenant = null;
    }

    /**
     * Définit le tenant courant à partir d'un modèle Tenant.
     */
    public static function setTenant(?Tenant $tenant): void
    {
        static::$tenant = $tenant;
        static::$tenantId = $tenant?->getKey();
    }

    /**
     * Identifiant du tenant courant, ou null en contexte global.
     */
    public static function currentId(): ?int
    {
        return static::$tenantId;
    }

    /**
     * Modèle du tenant courant (résolu paresseusement), ou null.
     */
    public static function current(): ?Tenant
    {
        if (static::$tenant === null && static::$tenantId !== null) {
            static::$tenant = Tenant::find(static::$tenantId);
        }

        return static::$tenant;
    }

    /**
     * Indique si l'on est en contexte global (super-admin / CLI sans tenant).
     */
    public static function isGlobal(): bool
    {
        return static::$tenantId === null;
    }

    /**
     * Exécute un callback dans un contexte tenant donné, puis restaure l'état.
     *
     * Utile pour les jobs/commandes qui doivent travailler au nom d'un tenant.
     */
    public static function run(?int $tenantId, Closure $callback): mixed
    {
        $previous = static::$tenantId;

        static::set($tenantId);

        try {
            return $callback();
        } finally {
            static::set($previous);
        }
    }

    /**
     * Réinitialise le contexte (jobs, tests, CLI).
     */
    public static function forget(): void
    {
        static::$tenantId = null;
        static::$tenant = null;
    }
}
