<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aligne le schéma sur le contrat réel de l'API Partenaire PayMe
     * (cf. docs/FEUILLE_DE_ROUTE_PAYME.md §4.1).
     *
     * - payment_gateways : plus de sandbox/live ni d'identifiants d'application ;
     *   `endpoint` est l'adresse de base de l'API ; `client_fees_rate` devient
     *   un pourcentage entier optionnel (vide = réglage du compte marchand) ;
     *   `timeout_minutes` borne l'attente d'une confirmation.
     * - payments : trace de la passerelle (statut brut, opérateur, reçu,
     *   dernière vérification, nombre de vérifications).
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn(['app_id', 'pay_type_id', 'mode', 'client_fees_rate']);
        });

        Schema::table('payment_gateways', function (Blueprint $table) {
            // Pourcentage entier 0–100 ; null = réglage du compte marchand.
            $table->unsignedTinyInteger('client_fees_rate')->nullable()->after('endpoint');
            $table->unsignedSmallInteger('timeout_minutes')->default(15)->after('client_fees_rate');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_status', 60)->nullable()->after('gateway_reference');
            $table->string('provider', 30)->nullable()->after('gateway_status');
            $table->string('receipt_url', 255)->nullable()->after('provider');
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
