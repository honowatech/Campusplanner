<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal des envois SMS, avec dédup par clé d'idempotence et par contenu.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained('tenants')->cascadeOnDelete();
            $table->string('to');
            $table->text('message');
            $table->string('status')->default('pending'); // pending | sent | failed
            $table->string('provider_ref')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->text('error')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('dedup_key')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
