<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\TenantContext;
use Database\Seeders\CourseClassSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\RoomBlockingSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\TeacherBlockingSeeder;
use Database\Seeders\TeacherSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Planning\Database\Seeders\DatabaseSeeder as PlanningDatabaseSeeder;

/**
 * Réinitialise les données du tenant démo (utilitaire super-admin).
 *
 * Conserve la structure (départements), l'abonnement Premium et la config SMS ;
 * réinitialise les données métier, les comptes et l'historique (paiements, SMS).
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Réinitialise les données du tenant démo';

    public function handle(): int
    {
        $demo = Tenant::where('is_demo', true)->first();

        if (! $demo) {
            $this->error('Aucun tenant démo trouvé.');

            return self::FAILURE;
        }

        if (! $this->confirm("Réinitialiser les données du tenant démo [{$demo->slug}] ?", true)) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $this->wipe($demo);
        $this->reseed($demo);

        $this->info('✓ Tenant démo réinitialisé.');

        return self::SUCCESS;
    }

    /**
     * Supprime les données métier et l'historique du tenant démo.
     */
    private function wipe(Tenant $demo): void
    {
        $tables = [
            'planning_shift_plannings',
            'planning_plannings',
            'planning_workstations',
            'teacher_blockings',
            'room_blockings',
            'students',
            'teacher_course',
            'teachers',
            'classes',
            'rooms',
            'courses',
            'payments',
            'sms_logs',
            'users',
        ];

        foreach ($tables as $table) {
            DB::table($table)->where('tenant_id', $demo->id)->delete();
        }

        $this->line('Données du tenant démo supprimées.');
    }

    /**
     * Re-sème les données dans le contexte du tenant démo, puis réconcilie.
     */
    private function reseed(Tenant $demo): void
    {
        TenantContext::set($demo->id);
        setPermissionsTeamId($demo->id);

        try {
            $this->call(DemoAccountSeeder::class);
            $this->call(CourseSeeder::class);
            $this->call(RoomSeeder::class);
            $this->call(TeacherSeeder::class);
            $this->call(CourseClassSeeder::class);
            $this->call(UserSeeder::class);
            $this->call(StudentSeeder::class);
            $this->call(TeacherBlockingSeeder::class);
            $this->call(RoomBlockingSeeder::class);
            $this->call(PlanningDatabaseSeeder::class);
        } finally {
            TenantContext::forget();
            setPermissionsTeamId(null);
        }

        // Réconcilie les pivots/lignes sans tenant_id vers le tenant démo.
        $this->call(TenantSeeder::class);
    }
}
