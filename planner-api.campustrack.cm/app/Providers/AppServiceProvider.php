<?php

namespace App\Providers;

use App\Policies\PlanningPolicy;
use App\Policies\ShiftPlanningPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les entités du module Planning vivent hors d'App\Models :
        // leurs policies doivent être enregistrées explicitement.
        Gate::policy(Planning::class, PlanningPolicy::class);
        Gate::policy(ShiftPlanning::class, ShiftPlanningPolicy::class);
    }
}
