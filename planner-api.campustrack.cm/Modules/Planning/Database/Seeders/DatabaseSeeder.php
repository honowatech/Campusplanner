<?php

namespace Modules\Planning\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanningSeeder::class,
            ShiftPlanningSeeder::class,
        ]);
    }
}
