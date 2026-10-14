<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rend les réglages (planification, conflits) scopés par tenant.
     *
     * La ligne existante (id = 1) devient la config « plateforme » (tenant_id
     * NULL), puis est clonée vers chaque tenant existant afin que chaque école
     * démarre avec les mêmes réglages.
     */
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete();
        });

        $this->clonePlatformSettingsToExistingTenants();
    }

    /**
     * Duplique la config plateforme (tenant_id NULL) vers chaque tenant existant.
     */
    private function clonePlatformSettingsToExistingTenants(): void
    {
        $platform = DB::table('app_settings')->whereNull('tenant_id')->first();

        if (! $platform) {
            return;
        }

        $base = (array) $platform;
        unset($base['id'], $base['tenant_id']);

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $row = $base;
            $row['tenant_id'] = $tenantId;
            $row['created_at'] = now();
            $row['updated_at'] = now();

            DB::table('app_settings')->insert($row);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('app_settings')->whereNotNull('tenant_id')->delete();

        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
