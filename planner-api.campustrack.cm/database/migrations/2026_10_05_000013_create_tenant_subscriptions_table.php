<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abonnements des tenants aux packs.
     *
     * Statuts : trial | active | expired | canceled | past_due.
     * Renouvellement MANUEL (auto_renew = false par défaut) : un admin de
     * tenant souscrit un pack pour une période ; à échéance, le service le
     * passe à `expired`. L'unicité d'un abonnement « courant » est garantie
     * au niveau service (machine à états).
     */
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('pack_id')->nullable()->constrained('packs')->nullOnDelete();
            $table->string('status')->default('trial'); // trial | active | expired | canceled | past_due
            $table->string('billing_period')->default('monthly'); // monthly | annual
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
