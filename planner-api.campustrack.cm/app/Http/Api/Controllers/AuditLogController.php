<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\RoleCatalog;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Journal d'audit.
 *
 * - Super-admin : tous les événements (filtrable par `tenant_id` et `event`).
 * - Admin de tenant : uniquement les événements de son tenant.
 */
class AuditLogController extends Controller
{
    use HttpResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AuditLog::query()->with('user:id,name,email');

        if (! $user->hasRole(RoleCatalog::GLOBAL_ROLE)) {
            $query->where('tenant_id', $user->tenant_id);
        } elseif ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', (string) $request->string('event'));
        }

        $logs = $query->latest()->paginate($request->integer('per_page', 20));

        return $this->success(['logs' => $logs], "Journal d'audit récupéré");
    }
}
