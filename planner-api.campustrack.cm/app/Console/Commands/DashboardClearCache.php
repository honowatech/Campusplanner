<?php

namespace App\Console\Commands;

use App\Services\DashboardService;
use Illuminate\Console\Command;

class DashboardClearCache extends Command
{
    protected $signature = 'dashboard:clear-cache 
                            {--pattern= : Pattern de clés à supprimer (ex: overview:*)}';

    protected $description = 'Vide le cache du dashboard';

    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        parent::__construct();
        $this->dashboardService = $dashboardService;
    }

    public function handle(): int
    {
        $pattern = $this->option('pattern');

        if ($pattern) {
            $this->info("Vidage du cache avec pattern: {$pattern}");
            $this->dashboardService->clearCache($pattern);
        } else {
            $this->info('Vidage complet du cache du dashboard');
            $this->dashboardService->clearCache();
        }

        $this->info('✓ Cache vidé avec succès');

        return 0;
    }
}
