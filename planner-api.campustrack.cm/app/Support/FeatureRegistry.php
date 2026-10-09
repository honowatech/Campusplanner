<?php

namespace App\Support;

use App\Models\Feature;
use App\Models\Pack;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Registre des fonctionnalités (feature gating).
 *
 * Source de vérité du catalogue de fonctionnalités et de la composition des
 * packs. Les fonctionnalités sont synchronisées en base via `feature:sync`
 * (et `packs:seed`), mais la résolution « le tenant a-t-il cette feature ? »
 * interroge l'abonnement courant du tenant.
 *
 * Règles :
 * - Tenant démo (`is_demo=true`) : TOUTES les fonctionnalités, sans abonnement.
 * - Tenant avec un essai ou un pack actif : fonctionnalités du pack.
 * - Tenant sans abonnement : uniquement le socle (`core`).
 * - Super-admin (global) : toutes les fonctionnalités actives.
 */
class FeatureRegistry
{
    /**
     * Catalogue canonique des fonctionnalités.
     *
     * @return array<string, array{label: string, group: string, description?: string}>
     */
    public static function catalog(): array
    {
        return [
            'dashboard' => ['label' => 'Tableau de bord', 'group' => 'core', 'description' => "Vue d'ensemble de l'activité de l'école."],
            'departments' => ['label' => 'Départements', 'group' => 'core', 'description' => 'Gestion des départements et de leurs responsables.'],
            'courses' => ['label' => 'Matières', 'group' => 'core', 'description' => 'Catalogue des matières et coefficients.'],
            'teachers' => ['label' => 'Enseignants', 'group' => 'core', 'description' => 'Gestion des enseignants et affectations.'],
            'students' => ['label' => 'Étudiants', 'group' => 'core', 'description' => 'Gestion des étudiants et inscriptions.'],
            'classes' => ['label' => 'Classes', 'group' => 'core', 'description' => 'Gestion des classes et de leur composition.'],
            'rooms' => ['label' => 'Salles', 'group' => 'core', 'description' => 'Gestion des salles et de leurs capacités.'],
            'scheduling' => ['label' => 'Planification', 'group' => 'core', 'description' => 'Création et génération des emplois du temps.'],
            'blockings' => ['label' => 'Indisponibilités', 'group' => 'core', 'description' => 'Gestion des indisponibilités salles et enseignants.'],
            'reports' => ['label' => 'Rapports', 'group' => 'advanced', 'description' => 'Rapports et statistiques avancés.'],
            'exports' => ['label' => 'Exports', 'group' => 'advanced', 'description' => 'Exports PDF et Excel.'],
            'ai-scheduling' => ['label' => 'Planification IA', 'group' => 'premium', 'description' => 'Génération des emplois du temps assistée par IA.'],
            'sms' => ['label' => 'Notifications SMS', 'group' => 'premium', 'description' => 'Envoi de SMS via votre compte Nexah.'],
            'roles-management' => ['label' => 'Rôles avancés', 'group' => 'premium', 'description' => 'Gestion fine des rôles et permissions.'],
        ];
    }

    /**
     * Groupes de fonctionnalités (pour l'UI).
     *
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'core' => 'Socle',
            'advanced' => 'Avancé',
            'premium' => 'Premium',
        ];
    }

    /**
     * Définition des packs à semer.
     *
     * @return array<string, array{name: string, tier: string, billing_period: string, price: int, sort: int, features: list<string>}>
     */
    public static function packs(): array
    {
        $core = self::coreKeys();
        $starter = array_values(array_unique(array_merge($core, ['reports'])));
        $standard = array_values(array_unique(array_merge($starter, ['exports', 'ai-scheduling'])));
        $premium = array_keys(self::catalog());

        $define = fn (string $name, string $tier, string $period, int $price, int $sort, array $features) => [
            'name' => $name,
            'tier' => $tier,
            'billing_period' => $period,
            'price' => $price,
            'sort' => $sort,
            'features' => $features,
        ];

        return [
            'starter-monthly' => $define('Starter', 'starter', 'monthly', 15000, 1, $starter),
            'starter-annual' => $define('Starter', 'starter', 'annual', 150000, 2, $starter),
            'standard-monthly' => $define('Standard', 'standard', 'monthly', 30000, 3, $standard),
            'standard-annual' => $define('Standard', 'standard', 'annual', 300000, 4, $standard),
            'premium-monthly' => $define('Premium', 'premium', 'monthly', 60000, 5, $premium),
            'premium-annual' => $define('Premium', 'premium', 'annual', 600000, 6, $premium),
        ];
    }

    /**
     * Clés des fonctionnalités du socle (groupe `core`).
     *
     * @return list<string>
     */
    public static function coreKeys(): array
    {
        return array_keys(array_filter(
            self::catalog(),
            fn (array $meta) => ($meta['group'] ?? 'core') === 'core'
        ));
    }

    /**
     * Fonctionnalités actives (modèles).
     */
    public static function allActive(): \Illuminate\Support\Collection
    {
        return Feature::query()->where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Synchronise le catalogue des fonctionnalités en base.
     */
    public static function syncCatalog(): int
    {
        $synced = 0;

        foreach (self::catalog() as $key => $meta) {
            Feature::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $meta['label'],
                    'group' => $meta['group'],
                    'description' => $meta['description'] ?? null,
                ]
            );
            $synced++;
        }

        return $synced;
    }

    /**
     * Synchronise les packs et leurs liens vers les fonctionnalités.
     */
    public static function syncPacks(): int
    {
        $synced = 0;

        foreach (self::packs() as $slug => $meta) {
            $pack = Pack::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'tier' => $meta['tier'],
                    'billing_period' => $meta['billing_period'],
                    'price' => $meta['price'],
                    'is_active' => true,
                    'sort' => $meta['sort'],
                ]
            );

            $featureIds = Feature::query()->whereIn('key', $meta['features'])->pluck('id');
            $pack->features()->sync($featureIds);
            $synced++;
        }

        return $synced;
    }

    /**
     * Indique si le tenant dispose de la fonctionnalité donnée.
     */
    public static function has(Tenant $tenant, string $key): bool
    {
        if ($tenant->is_demo) {
            return true;
        }

        return in_array($key, self::enabledFeatures($tenant), true);
    }

    /**
     * Clés de fonctionnalités actives pour un tenant.
     *
     * @return list<string>
     */
    public static function enabledFeatures(Tenant $tenant): array
    {
        if ($tenant->is_demo) {
            // Le tenant démo dispose de TOUTES les fonctionnalités du catalogue,
            // indépendamment de l'état de la table `features` (elle peut être
            // vide si `feature:sync`/`packs:seed` n'ont pas encore tourné).
            return array_keys(self::catalog());
        }

        $cacheKey = "tenant_features:{$tenant->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenant) {
            $subscription = $tenant->subscriptions()
                ->whereIn('status', [TenantSubscription::STATUS_TRIAL, TenantSubscription::STATUS_ACTIVE])
                ->latest('starts_at')
                ->first();

            if (! $subscription || ! $subscription->pack) {
                return self::coreKeys();
            }

            return $subscription->pack->features()
                ->where('is_active', true)
                ->pluck('key')
                ->all();
        });
    }

    /**
     * Clés de fonctionnalités actives pour un utilisateur donné.
     *
     * @return list<string>
     */
    public static function enabledForUser(User $user): array
    {
        if ($user->hasRole(RoleCatalog::GLOBAL_ROLE)) {
            return self::allActive()->pluck('key')->all();
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return [];
        }

        return self::enabledFeatures($tenant);
    }

    /**
     * Invalide le cache des fonctionnalités d'un tenant.
     */
    public static function flushCache(int|Tenant $tenant): void
    {
        $id = $tenant instanceof Tenant ? $tenant->id : $tenant;

        Cache::forget("tenant_features:{$id}");
    }

    /**
     * Invalide le cache des fonctionnalités de tous les tenants.
     */
    public static function flushCacheForAll(): void
    {
        foreach (Tenant::query()->pluck('id') as $id) {
            Cache::forget("tenant_features:{$id}");
        }
    }
}
