<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Garantit que le tenant démo persistant existe, est marqué `is_demo` et a le
 * mode démo activé par défaut (pour la découverte). Idempotent.
 *
 * L'abonnement Premium du tenant démo est géré par PackSeeder ; les comptes
 * démo par DemoAccountSeeder ; le backfill des données par TenantSeeder.
 */
class DemoTenantSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $demo = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Démo CampusTrack',
                'status' => 'active',
                'is_demo' => true,
                'demo_mode' => true,
            ]
        );

        if (! $demo->is_demo || ! $demo->demo_mode) {
            $demo->update(['is_demo' => true, 'demo_mode' => true]);
        }

        $this->command->info("Demo tenant ready [slug: {$demo->slug}] — mode démo activé.");
    }
}
