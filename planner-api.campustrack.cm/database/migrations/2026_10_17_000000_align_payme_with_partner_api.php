<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aligne le schéma sur le contrat réel de l'API Partenaire PayMe
     * (cf. docs/FEUILLE_DE_ROUTE_PAYME.md § 4.1).
     *
     * - `payment_gateways` : suppression de `app_id`, `pay_type_id`, `mode`
     *   (inexistants côté PayMe) ; `client_fees_rate` devient un entier 0–100
     *   nullable (vide = réglage du compte marchand) ; ajout de `timeout_minutes`.
     * - `payments` : suivi de la vérification (`gateway_status`, `provider`,
     *   `receipt_url`, `checked_at`, `checks`).
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn(['app_id', 'pay_type_id', 'mode', 'client_fees_rate']);
        });

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->unsignedTinyInteger('client_fees_rate')->nullable()->after('endpoint');
            $table->unsignedSmallInteger('timeout_minutes')->default(15)->after('client_fees_rate');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_status', 60)->nullable()->after('status');
            $table->string('provider', 30)->nullable()->after('payment_method');
            $table->string('receipt_url')->nullable()->after('provider');
            $table->timestamp('checked_at')->nullable()->after('paid_at');
            $table->unsignedSmallInteger('checks')->default(0)->after('checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_status', 'provider', 'receipt_url', 'checked_at', 'checks']);
        });

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn(['client_fees_rate', 'timeout_minutes']);
        });

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('app_id')->nullable();
            $table->string('pay_type_id')->nullable();
            $table->decimal('client_fees_rate', 6, 4)->default(0);
            $table->string('mode')->default('sandbox');
        });
    }
};
