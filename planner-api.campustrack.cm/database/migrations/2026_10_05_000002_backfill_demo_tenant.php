<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables de données à rattacher au tenant démo (hors `users`, traité
     * séparément pour préserver les comptes super-admin globaux).
     *
     * @return list<string>
     */
    private function dataTables(): array
    {
        return [
            'departments',
            'courses',
            'teachers',
            'teacher_course',
            'classes',
            'students',
            'rooms',
            'room_blockings',
            'teacher_blockings',
            'planning_plannings',
            'planning_shift_plannings',
            'planning_workstations',
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Le tenant démo est persistant : il est créé ici (déterministe, slug « demo »).
        $demoId = DB::table('tenants')->insertGetId([
            'name' => 'Démo CampusTrack',
            'slug' => 'demo',
            'status' => 'active',
            'is_demo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rattache les données existantes (déploiement prod) au tenant démo.
        foreach ($this->dataTables() as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $demoId]);
        }

        // Tous les utilisateurs existants rejoignent le tenant démo…
        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $demoId]);

        // …sauf les comptes super-admin, qui restent globaux (tenant_id NULL).
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('mh.model_id')
                    ->from('model_has_roles as mh')
                    ->join('roles as r', 'r.id', '=', 'mh.role_id')
                    ->where('r.name', 'super-admin')
                    ->where('mh.model_type', 'App\Models\User');
            })
            ->update(['tenant_id' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Simple nettoyage : on remet les données en contexte global et on
        // supprime le tenant démo créé par cette migration.
        $demo = DB::table('tenants')->where('slug', 'demo')->first();

        if ($demo) {
            foreach (array_merge($this->dataTables(), ['users']) as $table) {
                DB::table($table)->where('tenant_id', $demo->id)->update(['tenant_id' => null]);
            }

            DB::table('tenants')->where('id', $demo->id)->delete();
        }
    }
};
