<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cycle de vie des tenants (console super-admin + onboarding).
 *
 * La création d'un tenant amorce le parcours complet : rôles par tenant,
 * compte admin initial (approuvé d'office) et essai gratuit.
 */
class TenantService
{
    public function __construct(protected TenantSubscriptionsService $subscriptions) {}

    /**
     * Crée un tenant, sème ses rôles, crée le compte admin initial et démarre
     * l'essai gratuit. Retourne le tenant créé.
     *
     * @param  array{name: string, slug: string, admin_name: string, admin_email: string, admin_password: string, trial_days?: int}  $data
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['slug']),
                'status' => 'active',
                'is_demo' => false,
            ]);

            RoleCatalog::seedTenantRoles($tenant->id);

            $this->createInitialAdmin($tenant, $data);

            $this->subscriptions->startTrial($tenant, $data['trial_days'] ?? 14);

            AuditService::record('tenant.created', $tenant, [], [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ]);

            return $tenant;
        });
    }

    /**
     * Suspend le tenant (bloque l'accès à ses utilisateurs).
     */
    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => 'suspended']);

        AuditService::record('tenant.suspended', $tenant, ['status' => 'active'], ['status' => 'suspended']);

        return $tenant;
    }

    /**
     * Réactive un tenant suspendu.
     */
    public function reactivate(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => 'active']);

        AuditService::record('tenant.reactivated', $tenant, ['status' => 'suspended'], ['status' => 'active']);

        return $tenant;
    }

    /**
     * Crée le compte administrateur initial du tenant.
     *
     * @param  array{admin_name: string, admin_email: string, admin_password: string}  $data
     */
    private function createInitialAdmin(Tenant $tenant, array $data): User
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId($tenant->id);

        try {
            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'tenant_id' => $tenant->id,
                'is_approved' => true,
                'is_demo' => false,
                'email_verified_at' => now(),
            ]);

            $admin->assignRole('administrateur');

            return $admin;
        } finally {
            setPermissionsTeamId($previous);
        }
    }
}
