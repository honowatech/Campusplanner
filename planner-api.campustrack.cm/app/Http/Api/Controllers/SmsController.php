<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SmsCredential;
use App\Models\SmsLog;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\SmsService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    use HttpResponses;

    /**
     * Crédences SMS du tenant (mot de passe masqué).
     */
    public function settings(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        return $this->success(
            ['credential' => $this->credentialPayload($tenant->smsCredential)],
            'Configuration SMS récupérée'
        );
    }

    /**
     * Met à jour les crédences SMS du tenant (chiffrées en base).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'user' => 'sometimes|string|max:255',
            'password' => 'sometimes|nullable|string|max:255',
            'sender_id' => 'sometimes|nullable|string|max:11',
            'is_active' => 'sometimes|boolean',
        ]);

        $credential = SmsCredential::query()->firstOrNew(
            ['tenant_id' => $tenant->id],
            ['provider' => 'nexah']
        );

        if (array_key_exists('user', $validated)) {
            $credential->user = $validated['user'];
        }

        if (! empty($validated['password'])) {
            $credential->password = $validated['password'];
        }

        if (array_key_exists('sender_id', $validated)) {
            $credential->sender_id = $validated['sender_id'] ?? 'CAMPUS';
        }

        if (array_key_exists('is_active', $validated)) {
            $credential->is_active = $validated['is_active'];
        }

        $credential->save();

        AuditService::record('sms_credentials.updated', $credential, [], [
            'user' => $credential->user,
            'sender_id' => $credential->sender_id,
            'is_active' => (bool) $credential->is_active,
        ]);

        return $this->success(
            ['credential' => $this->credentialPayload($credential)],
            'Configuration SMS mise à jour'
        );
    }

    /**
     * Envoi d'un SMS (avec clé d'idempotence via l'en-tête Idempotency-Key).
     */
    public function send(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'to' => 'required|string|max:20',
            'message' => 'required|string|max:640',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        $log = app(SmsService::class)->send(
            $tenant,
            $validated['to'],
            $validated['message'],
            $idempotencyKey,
        );

        return $this->success(['log' => $log], 'SMS envoyé', 201);
    }

    /**
     * Envoi d'un SMS de test (même mécanique, sans clé d'idempotence requise).
     */
    public function test(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'to' => 'required|string|max:20',
            'message' => 'sometimes|string|max:640',
        ]);

        $log = app(SmsService::class)->send(
            $tenant,
            $validated['to'],
            $validated['message'] ?? 'Test SMS CampusTrack',
        );

        return $this->success(['log' => $log], 'SMS de test envoyé', 201);
    }

    /**
     * Journal des envois SMS du tenant.
     */
    public function logs(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $logs = SmsLog::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(['logs' => $logs], 'Journal SMS récupéré');
    }

    /**
     * Résout le tenant de l'utilisateur courant (les routes sont déjà
     * protégées par `feature:sms`, qui laisse passer le super-admin ; le
     * super-admin n'a pas de tenant et ne peut donc pas envoyer de SMS ici).
     */
    private function tenant(Request $request): Tenant
    {
        $tenant = $request->user()?->tenant;

        abort_if(! $tenant, 403, 'Aucun tenant associé à ce compte.');

        return $tenant;
    }

    /**
     * Sérialise une crédence sans exposer le mot de passe.
     *
     * @return array<string, mixed>|null
     */
    private function credentialPayload(?SmsCredential $credential): ?array
    {
        if (! $credential) {
            return null;
        }

        return [
            'id' => $credential->id,
            'provider' => $credential->provider,
            'user' => $credential->user,
            'sender_id' => $credential->sender_id,
            'is_active' => $credential->is_active,
            'balance_cached' => $credential->balance_cached,
        ];
    }
}
