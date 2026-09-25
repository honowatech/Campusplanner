<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use HttpResponses;

    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get overview stats
     *
     * Combines realtime and heavy stats
     */
    public function overview(Request $request): JsonResponse
    {
        $period = $request->get('period', '7d');
        $departmentId = $this->getDepartmentId($request);

        // Validate period
        if (! in_array($period, ['24h', '7d', '30d'])) {
            return $this->error(null, 'Période invalide. Utilisez: 24h, 7d, 30d', 422);
        }

        $realtime = $this->dashboardService->getOverviewRealtime($departmentId);
        $heavy = $this->dashboardService->getOverviewHeavy($period, $departmentId);

        return $this->success([
            'realtime' => $realtime,
            'heavy' => $heavy,
            'period' => $period,
            'department_id' => $departmentId,
            'generated_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Statistiques d\'aperçu récupérées', 200);
    }

    /**
     * Get department stats
     */
    public function departments(Request $request): JsonResponse
    {
        $period = $request->get('period', '7d');

        if (! in_array($period, ['24h', '7d', '30d'])) {
            return $this->error(null, 'Période invalide', 422);
        }

        $stats = $this->dashboardService->getDepartmentStats($period);

        return $this->success([
            'departments' => $stats,
            'period' => $period,
            'generated_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Statistiques par département récupérées', 200);
    }

    /**
     * Get resource utilization
     */
    public function resources(Request $request): JsonResponse
    {
        $period = $request->get('period', '7d');
        $departmentId = $this->getDepartmentId($request);

        if (! in_array($period, ['24h', '7d', '30d'])) {
            return $this->error(null, 'Période invalide', 422);
        }

        $resources = $this->dashboardService->getResourceUtilization($period, $departmentId);

        return $this->success([
            'resources' => $resources,
            'period' => $period,
            'department_id' => $departmentId,
            'generated_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Utilisation des ressources récupérée', 200);
    }

    /**
     * Get alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        $departmentId = $this->getDepartmentId($request);
        $page = $request->get('page', 1);

        $alerts = $this->dashboardService->getAlerts($departmentId);

        // Pagination manuelle pour les alertes
        $perPage = config('dashboard.pagination.alerts_per_page', 10);
        $total = count($alerts['critical']) + count($alerts['warnings']) + count($alerts['info']);

        return $this->success([
            'alerts' => $alerts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'department_id' => $departmentId,
            'generated_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Alertes récupérées', 200);
    }

    /**
     * Get recent activity
     */
    public function activity(Request $request): JsonResponse
    {
        $period = $request->get('period', '7d');
        $departmentId = $this->getDepartmentId($request);
        $page = $request->get('page', 1);

        if (! in_array($period, ['24h', '7d', '30d'])) {
            return $this->error(null, 'Période invalide', 422);
        }

        $activity = $this->dashboardService->getRecentActivity($period, $departmentId);

        return $this->success([
            'activity' => $activity,
            'pagination' => [
                'current_page' => $page,
                'per_page' => config('dashboard.pagination.activity_per_page', 10),
                'total' => count($activity),
            ],
            'period' => $period,
            'department_id' => $departmentId,
            'generated_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Activité récente récupérée', 200);
    }

    /**
     * Get charts data
     */
    public function charts(Request $request): JsonResponse
    {
        $period = $request->get('period', '7d');
        $departmentId = $this->getDepartmentId($request);

        if (! in_array($period, ['24h', '7d', '30d'])) {
            return $this->error(null, 'Période invalide', 422);
        }

        $charts = $this->dashboardService->getChartsData($period, $departmentId);

        return $this->success([
            'charts' => $charts,
            'period' => $period,
            'department_id' => $departmentId,
            'generated_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Données des graphiques récupérées', 200);
    }

    /**
     * Refresh cache (super-admin only)
     */
    public function refreshCache(Request $request): JsonResponse
    {
        $pattern = $request->get('pattern');

        $this->dashboardService->clearCache($pattern);

        return $this->success([
            'message' => $pattern
                ? "Cache '{$pattern}' vidé avec succès"
                : 'Tout le cache du dashboard a été vidé',
            'cleared_at' => now()->format('Y-m-d\TH:i:s\Z'),
        ], 'Cache rafraîchi', 200);
    }

    /**
     * Get department ID based on user role
     */
    private function getDepartmentId(Request $request): ?int
    {
        $user = $request->user();

        // Super-admin can filter by any department
        if ($user->hasRole('super-admin')) {
            return $request->get('department_id');
        }

        // Admin can only see their own department
        if ($user->hasRole('administrateur')) {
            // Get department from user's teacher profile or direct relation
            return $user->department_id;
        }

        return null;
    }
}
