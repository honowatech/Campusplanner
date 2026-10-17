<?php

use App\Services\PaymentService;
use App\Services\TenantSubscriptionsService;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Dashboard cache warming - Stats temps réel (toutes les 5 minutes)
Schedule::command('dashboard:warm-cache realtime')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Dashboard cache warming - Stats lourdes période 24h (toutes les heures)
Schedule::command('dashboard:warm-cache heavy --period=24h')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Dashboard cache warming - Stats lourdes période 7d (toutes les heures)
Schedule::command('dashboard:warm-cache heavy --period=7d')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Dashboard cache warming - Stats lourdes période 30d (toutes les heures)
Schedule::command('dashboard:warm-cache heavy --period=30d')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Réconciliation PayMe : PayMe n'envoie aucune notification, les paiements en
// attente sont vérifiés activement. Appel direct (pas de file) : ne dépend pas
// d'un `queue:work` permanent, seulement du cron `schedule:run`.
Schedule::call(fn () => app(PaymentService::class)->reconcilePending())
    ->name('payme:reconcile')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Expiration des abonnements et essais échus (retour au socle).
Schedule::call(fn () => app(TenantSubscriptionsService::class)->markExpired())
    ->name('subscriptions:expire')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
