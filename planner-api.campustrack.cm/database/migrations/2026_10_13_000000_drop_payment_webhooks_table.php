<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supprime la table des callbacks PayMe : la confirmation repose
     * exclusivement sur le polling (aucun webhook — cf. MAMONIPAY_API.md).
     */
    public function up(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }

    public function down(): void
    {
        // Re-création minimale pour permettre un éventuel rollback.
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique();
            $table->json('payload');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }
};
