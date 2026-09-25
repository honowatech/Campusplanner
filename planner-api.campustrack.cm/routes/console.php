<?php

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
