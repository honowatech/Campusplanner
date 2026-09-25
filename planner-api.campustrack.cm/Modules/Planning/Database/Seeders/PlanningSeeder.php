<?php

namespace Modules\Planning\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanningSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $plannings = [
            [
                'type' => 'weekly',
                'starting_date' => '2025-09-01',
                'ending_date' => '2026-06-30',
                'description' => 'Année académique 2025-2026 - Planification hebdomadaire',
            ],
            [
                'type' => 'weekly',
                'starting_date' => '2025-09-01',
                'ending_date' => '2026-01-31',
                'description' => 'Semestre 1 - Année académique 2025-2026',
            ],
            [
                'type' => 'weekly',
                'starting_date' => '2026-02-02',
                'ending_date' => '2026-06-30',
                'description' => 'Semestre 2 - Année académique 2025-2026',
            ],
            [
                'type' => 'monthly',
                'starting_date' => '2026-09-01',
                'ending_date' => '2027-06-30',
                'description' => 'Année académique 2026-2027',
            ],
        ];

        foreach ($plannings as $planning) {
            $exists = DB::table('planning_plannings')
                ->where('starting_date', $planning['starting_date'])
                ->where('ending_date', $planning['ending_date'])
                ->exists();

            if (! $exists) {
                DB::table('planning_plannings')->insert([
                    ...$planning,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Plannings seeded successfully! ('.count($plannings).' plannings)');
    }
}
