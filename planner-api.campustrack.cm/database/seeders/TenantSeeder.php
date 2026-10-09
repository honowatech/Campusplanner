<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crée (ou récupère) le tenant démo persistant et rattache l'ensemble des
 * données seedées au tenant démo, à l'exception des comptes super-admin qui
 * restent globaux (tenant_id NULL).
 *
 * Ce seeder doit s'exécuter EN DERNIER : il ne fait que réconcilier les données
 * créées par les autres seeders avec le tenant démo.
 */
class TenantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Tables de données métier (hors users) à rattacher au tenant démo.
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
     * Run the database seeds.
     */
    public function run(): void
    {
        $demo = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Démo CampusTrack',
                'status' => 'active',
                'is_demo' => true,
            ]
        );

        $demoId = $demo->id;

        foreach ($this->dataTables() as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $demoId]);
        }

        // Tous les utilisateurs (démo, test, …) rejoignent le tenant démo…
        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $demoId]);

        // …sauf les comptes super-admin, qui restent globaux.
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('mh.model_id')
                    ->from('model_has_roles as mh')
                    ->join('roles as r', 'r.id', '=', 'mh.role_id')
                    ->where('r.name', 'super-admin')
                    ->where('mh.model_type', User::class);
            })
            ->update(['tenant_id' => null]);

        $this->command->info("Demo tenant ready [slug: {$demo->slug}] — données rattachées (tenant_id = {$demoId}).");
    }
}
