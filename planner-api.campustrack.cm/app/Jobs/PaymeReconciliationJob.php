<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Réconciliation de secours : interroge PayMe pour les paiements en attente
 * (en complément du webhook). Planifiée avec `withoutOverlapping`.
 */
class PaymeReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PaymentService $paymentService): void
    {
        $paymentService->reconcilePending();
    }
}
