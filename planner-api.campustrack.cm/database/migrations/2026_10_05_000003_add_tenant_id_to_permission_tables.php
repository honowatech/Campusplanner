<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne `tenant_id` aux tables spatie pour un déploiement
 * existant (dont les tables ont été créées avec `teams = false`).
 *
 * Sur une installation neuve, `create_permission_tables` (qui lit la config
 * `teams = true`) crée déjà ces colonnes : chaque bloc est donc gardé par
 * `hasColumn` pour rester idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addTenantIdToRoles();
        $this->addTenantIdToModelHasRoles();
        $this->addTenantIdToModelHasPermissions();
    }

    public function down(): void
    {
        foreach (['model_has_permissions', 'model_has_roles', 'roles'] as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }

    private function addTenantIdToRoles(): void
    {
        if (Schema::hasColumn('roles', 'tenant_id')) {
            return;
        }

        // L'unicité (name, guard_name) devient (tenant_id, name, guard_name)
        // pour permettre le même nom de rôle dans plusieurs tenants.
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });
    }

    private function addTenantIdToModelHasRoles(): void
    {
        if (Schema::hasColumn('model_has_roles', 'tenant_id')) {
            return;
        }

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->index('tenant_id');
        });
    }

    private function addTenantIdToModelHasPermissions(): void
    {
        if (Schema::hasColumn('model_has_permissions', 'tenant_id')) {
            return;
        }

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->index('tenant_id');
        });
    }
};
