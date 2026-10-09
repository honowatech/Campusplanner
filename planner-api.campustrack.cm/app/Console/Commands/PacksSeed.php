<?php

namespace App\Console\Commands;

use App\Support\FeatureRegistry;
use Illuminate\Console\Command;

class PacksSeed extends Command
{
    protected $signature = 'packs:seed';

    protected $description = 'Sème les packs d\'abonnement et leurs fonctionnalités';

    public function handle(): int
    {
        $features = FeatureRegistry::syncCatalog();
        $packs = FeatureRegistry::syncPacks();

        $this->info("✓ {$features} fonctionnalités et {$packs} packs synchronisés.");

        return self::SUCCESS;
    }
}
