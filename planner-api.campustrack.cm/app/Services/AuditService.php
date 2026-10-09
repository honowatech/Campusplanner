<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Journal d'audit des événements sensibles.
 *
 * Usage (méthode statique, sans injection) :
 *   AuditService::record('payment.paid', $payment, ['status' => 'pending'], ['status' => 'paid']);
 *
 * L'acteur est résolu depuis la session (null pour un job/CLI). Le tenant est
 * résolu depuis l'entité (colonne `tenant_id`) ou, à défaut, depuis l'acteur.
 */
class AuditService
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function record(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        $user = Auth::guard('sanctum')->user();

        return AuditLog::create([
            'tenant_id' => self::resolveTenantId($auditable, $user),
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Résout l'identifiant du tenant concerné.
     */
    private static function resolveTenantId(?Model $auditable, mixed $user): ?int
    {
        if ($auditable instanceof Tenant) {
            return $auditable->id;
        }

        if ($auditable && array_key_exists('tenant_id', $auditable->getAttributes())) {
            $tenantId = $auditable->getAttribute('tenant_id');

            return $tenantId !== null ? (int) $tenantId : null;
        }

        return $user?->tenant_id;
    }
}
