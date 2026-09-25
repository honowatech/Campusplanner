<?php

use App\Http\Middleware\TestConnexion;
use Illuminate\Support\Facades\Route;
use Modules\Planning\Http\Controllers\DashboardController;
use Modules\Planning\Http\Controllers\PlanningController;
use Modules\Planning\Http\Controllers\ShiftPlanningController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['web', TestConnexion::class])->prefix('plannings')->name('planning.')->group(function () {
    Route::resource('plannings', PlanningController::class)->except('create', 'edit', 'show');
    Route::resource('shift-plannings', ShiftPlanningController::class);
    Route::get('form/plannings', [PlanningController::class, 'getForm'])->name('plannings.form');
    Route::get('form/shift-plannings', [ShiftPlanningController::class, 'getForm'])->name('shift-plannings.form');
    Route::get('filter/shift-plannings', [ShiftPlanningController::class, 'getList'])->name('shift-plannings.list');
    Route::get('enseignants/shift-plannings', [ShiftPlanningController::class, 'enseignantsSelect'])->name('enseignants.select');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    //     Route::get('dashboard/shift-plannings', [DashboardController::class, 'shiftPlanning'])->name('dashboard.shift-plannings');
});
