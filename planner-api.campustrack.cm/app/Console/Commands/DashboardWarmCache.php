<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\DashboardService;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class DashboardWarmCache extends Command
{
    protected $signature = 'dashboard:warm-cache 
                            {type : Type de cache (realtime|heavy|all)}
                            {--period=7d : Période pour les stats lourdes (24h|7d|30d)}
                            {--tenant= : ID du tenant à réchauffer (défaut : tous les tenants + global)}
                            {--department= : ID du département spécifique (super-admin uniquement)}';

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
        $tenantId = $this->option('tenant');

        if (! in_array($type, ['realtime', 'heavy', 'all'], true)) {
            $this->error('Type invalide. Utilisez: realtime, heavy, all');

            return 1;
        }

        $startTime = microtime(true);

        try {
            if ($tenantId !== null) {
                $this->warmTenant((int) $tenantId, $type, $period);
            } else {
                // Contexte global (super-admin) : vue agrégée, optionnellement
                // filtrée par département.
                $this->warmInContext(null, $type, $period, $departmentId);

                // Chaque tenant : réchauffe sa vue propre (clé scopée par tenant_id).
                foreach (Tenant::query()->pluck('id') as $id) {
                    $this->warmTenant((int) $id, $type, $period);
                }
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Cache pré-calculé avec succès en {$duration}s");

            return 0;

        } catch (\Exception $e) {
            $this->error('Erreur: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Réchauffe le cache d'un tenant dans son propre contexte, afin que la
     * clé générée par DashboardService inclue bien son `tenant_id`.
     */
    private function warmTenant(int $tenantId, string $type, string $period): void
    {
        TenantContext::run($tenantId, function () use ($type, $period) {
            $this->warmInContext($tenantId, $type, $period, null);
        });
    }

    /**
     * Réchauffe un jeu de clés (realtime/heavy/all) pour un contexte donné.
     */
    private function warmInContext(?int $tenantId, string $type, string $period, ?int $departmentId): void
    {
        $scope = $tenantId === null ? 'global' : "tenant {$tenantId}";
        $this->info("Calcul des stats ({$scope}, type: {$type}, période: {$period})");

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
        }
    }

    private function warmRealtimeCache(?int $departmentId): void
    {
        $this->dashboardService->getOverviewRealtime($departmentId);
        $this->dashboardService->getCurrentSessions($departmentId);
        $this->dashboardService->getAlerts($departmentId);
    }

    private function warmHeavyCache(string $period, ?int $departmentId): void
    {
        $this->dashboardService->getOverviewHeavy($period, $departmentId);
        $this->dashboardService->getDepartmentStats($period);
        $this->dashboardService->getResourceUtilization($period, $departmentId);
        $this->dashboardService->getRecentActivity($period, $departmentId);
        $this->dashboardService->getChartsData($period, $departmentId);
    }
}
