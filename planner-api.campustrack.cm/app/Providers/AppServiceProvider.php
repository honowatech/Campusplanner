<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\PlanningPolicy;
use App\Policies\ShiftPlanningPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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

        // Le lien de réinitialisation ouvre la page du front (SPA), pas l'API.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]));

        // Email dans la langue de la requête (en-tête Accept-Language).
        ResetPassword::toMailUsing(function (User $user, string $token) {
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject(__('api.password_reset.mail_subject', ['app' => config('app.name')]))
                ->greeting(__('api.password_reset.mail_greeting', ['name' => $user->name]))
                ->line(__('api.password_reset.mail_intro'))
                ->action(__('api.password_reset.mail_action'), call_user_func(ResetPassword::$createUrlCallback, $user, $token))
                ->line(__('api.password_reset.mail_expire', ['count' => $minutes]))
                ->line(__('api.password_reset.mail_ignore'));
        });
    }
}
