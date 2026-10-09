<?php

namespace App\Console\Commands;

use App\Support\FeatureRegistry;
use Illuminate\Console\Command;

class FeatureSync extends Command
{
    protected $signature = 'feature:sync';

    protected $description = 'Synchronise le catalogue des fonctionnalités en base';

    public function handle(): int
    {
        $count = FeatureRegistry::syncCatalog();

        $this->info("✓ {$count} fonctionnalités synchronisées.");

        return self::SUCCESS;
    }
}
