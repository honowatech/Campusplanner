<?php

namespace Database\Seeders;

use App\Models\Pack;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\FeatureRegistry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Sème les fonctionnalités, les packs et l'abonnement du tenant démo.
 *
 * Doit s'exécuter APRÈS TenantSeeder (qui crée le tenant démo).
 */
class PackSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        FeatureRegistry::syncCatalog();
        FeatureRegistry::syncPacks();

        $this->seedDemoSubscription();
    }

    /**
     * Le tenant démo dispose d'un abonnement Premium permanent (sans échéance).
     */
    private function seedDemoSubscription(): void
    {
        $demo = Tenant::where('is_demo', true)->first();

        if (! $demo) {
            return;
        }

        $premium = Pack::where('slug', 'premium-annual')->first();

        if (! $premium) {
            return;
        }

        if ($demo->subscriptions()->where('status', TenantSubscription::STATUS_ACTIVE)->exists()) {
            return;
        }

        TenantSubscription::create([
            'tenant_id' => $demo->id,
            'pack_id' => $premium->id,
            'status' => TenantSubscription::STATUS_ACTIVE,
            'billing_period' => TenantSubscription::PERIOD_ANNUAL,
            'starts_at' => now(),
            'ends_at' => null, // jamais expiré
            'auto_renew' => false,
        ]);
    }
}
