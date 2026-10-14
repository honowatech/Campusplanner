<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paiements d'abonnement par tenant (PayMe). Chaque référence unique est
     * indexée UNIQUE pour garantir l'idempotence (cf. §7 du plan).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('XAF');
            $table->string('status')->default('pending'); // pending | in_progress | paid | failed | refunded | deposited
            $table->string('gateway_reference')->nullable()->unique();
            $table->string('external_reference')->nullable()->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
