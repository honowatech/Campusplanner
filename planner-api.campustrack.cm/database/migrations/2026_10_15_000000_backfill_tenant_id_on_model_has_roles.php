<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache les rôles et permissions déjà attribués au tenant de leur utilisateur.
 *
 * Sur un déploiement existant, `backfill_demo_tenant` (000002) a rattaché les
 * utilisateurs au tenant démo, mais `tenant_id` n'existait pas encore sur
 * `model_has_roles` / `model_has_permissions` (ajouté par 000003). Ces lignes sont
 * donc restées à NULL : avec `teams = true`, spatie ne les voit plus dans le
 * contexte du tenant, et chaque utilisateur existant se connecte sans aucun rôle
 * (`DemoAccountSeeder` échoue aussi en clé primaire dupliquée en voulant recréer
 * le rôle pour le tenant). Les super-admins (tenant_id NULL sur users) restent
 * globaux : leurs lignes ne sont pas touchées.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            DB::table($table)
                ->where('model_type', User::class)
                ->whereNull('tenant_id')
                ->whereIn('model_id', fn ($query) => $query->select('id')->from('users')->whereNotNull('tenant_id'))
                ->update(['tenant_id' => DB::raw("(select users.tenant_id from users where users.id = {$table}.model_id)")]);
        }
    }

    public function down(): void
    {
        // Rien à défaire : avant cette migration, ces lignes étaient inutilisables.
    }
};
