<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuration PayMe (MamoniPay) de la plateforme, gérée par le
     * super-admin. Le mot de passe est chiffré (cast `encrypted`).
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('payme');
            $table->string('user_name')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('app_id')->nullable();
            $table->string('endpoint')->nullable(); // base URL (sandbox/live)
            $table->string('pay_type_id')->nullable();
            $table->decimal('client_fees_rate', 6, 4)->default(0); // 0.01 = 1%
            $table->string('mode')->default('sandbox'); // sandbox | live
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
