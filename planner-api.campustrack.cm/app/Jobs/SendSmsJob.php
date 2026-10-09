<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\SmsService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envoi SMS asynchrone. Le contexte tenant est propagé via TenantContext::run
 * (cf. §7 du plan : propagation `tenant_id` dans les jobs).
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public string $to,
        public string $message,
        public ?string $idempotencyKey = null,
    ) {}

    public function handle(SmsService $smsService): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        TenantContext::run($this->tenantId, function () use ($smsService, $tenant) {
            $smsService->send($tenant, $this->to, $this->message, $this->idempotencyKey);
        });
    }
}
