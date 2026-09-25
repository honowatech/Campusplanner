<?php

namespace App\Console\Commands;

use App\Services\DashboardService;
use Illuminate\Console\Command;

class DashboardWarmCache extends Command
{
    protected $signature = 'dashboard:warm-cache 
                            {type : Type de cache (realtime|heavy|all)}
                            {--period=7d : Période pour les stats lourdes (24h|7d|30d)}
                            {--department= : ID du département spécifique}';

    protected $description = 'Pré-calcule et met en cache les statistiques du dashboard';

    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        parent::__construct();
        $this->dashboardService = $dashboardService;
    }

    public function handle(): int
    {
        $type = $this->argument('type');
        $period = $this->option('period');
        $departmentId = $this->option('department');

        $this->info("Pré-calcul du cache type: {$type}, période: {$period}");

        $startTime = microtime(true);

        try {
            switch ($type) {
                case 'realtime':
                    $this->warmRealtimeCache($departmentId);
                    break;

                case 'heavy':
                    $this->warmHeavyCache($period, $departmentId);
                    break;

                case 'all':
                    $this->warmRealtimeCache($departmentId);
                    $this->warmHeavyCache($period, $departmentId);
                    break;

                default:
                    $this->error('Type invalide. Utilisez: realtime, heavy, all');

                    return 1;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Cache pré-calculé avec succès en {$duration}s");

            return 0;

        } catch (\Exception $e) {
            $this->error('Erreur: '.$e->getMessage());

            return 1;
        }
    }

    private function warmRealtimeCache(?int $departmentId): void
    {
        $this->info('Calcul des stats temps réel...');

        $this->dashboardService->getOverviewRealtime($departmentId);
        $this->dashboardService->getCurrentSessions($departmentId);
        $this->dashboardService->getAlerts($departmentId);

        $this->info('✓ Stats temps réel mises en cache');
    }

    private function warmHeavyCache(string $period, ?int $departmentId): void
    {
        $this->info("Calcul des stats lourdes (période: {$period})...");

        $this->dashboardService->getOverviewHeavy($period, $departmentId);
        $this->dashboardService->getDepartmentStats($period);
        $this->dashboardService->getResourceUtilization($period, $departmentId);
        $this->dashboardService->getRecentActivity($period, $departmentId);
        $this->dashboardService->getChartsData($period, $departmentId);

        $this->info('✓ Stats lourdes mises en cache');
    }
}
