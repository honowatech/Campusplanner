<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crédences SMS d'un tenant (une seule par tenant).
     * Le mot de passe Nexah est chiffré via le cast `encrypted`.
     */
    public function up(): void
    {
        Schema::create('sms_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('provider')->default('nexah');
            $table->string('user')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('sender_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->decimal('balance_cached', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credentials');
    }
};
