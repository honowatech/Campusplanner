<?php

use Illuminate\Support\Facades\Route;
use Modules\Planning\Http\Controllers\PlanningController;
use Modules\Planning\Http\Controllers\SchedulingController;
use Modules\Planning\Http\Controllers\ShiftPlanningController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->prefix('plannings')->name('planning.')->group(function () {
    // Resource routes pour plannings - uniquement API (pas de vues)
    Route::get('', [PlanningController::class, 'index'])->name('plannings.index');
    Route::post('', [PlanningController::class, 'store'])->name('plannings.store');
    Route::get('/{id}', [PlanningController::class, 'show'])->name('plannings.show')->whereNumber('id');
    Route::put('/{id}', [PlanningController::class, 'update'])->name('plannings.update')->whereNumber('id');
    Route::delete('/{id}', [PlanningController::class, 'destroy'])->name('plannings.destroy')->whereNumber('id');

    // Resource routes pour shift-plannings - uniquement API (pas de vues)
    Route::get('shift-plannings', [ShiftPlanningController::class, 'index'])->name('shift-plannings.index');
    Route::post('shift-plannings', [ShiftPlanningController::class, 'store'])->name('shift-plannings.store');
    Route::put('shift-plannings/{id}', [ShiftPlanningController::class, 'update'])->name('shift-plannings.update');
    Route::delete('shift-plannings/{id}', [ShiftPlanningController::class, 'destroy'])->name('shift-plannings.destroy');

    // Route utilitaire pour sélection des enseignants
    Route::get('enseignants/shift-plannings', [ShiftPlanningController::class, 'teachersSelect'])->name('enseignants.select');

    // Routes pour la génération automatique et gestion des conflits
    Route::post('generate', [SchedulingController::class, 'generate'])->name('generate');
    Route::post('detect-conflicts', [SchedulingController::class, 'detectConflicts'])->name('detect-conflicts');
    Route::post('resolve-conflicts', [SchedulingController::class, 'resolveConflicts'])->name('resolve-conflicts');
    Route::get('statistics/{planning_id}', [SchedulingController::class, 'statistics'])->name('statistics');
    Route::post('optimize/{planning_id}', [SchedulingController::class, 'optimize'])->name('optimize');

    // Routes pour les événements récurrents
    Route::post('shift-plannings/{id}/make-recurring', [SchedulingController::class, 'makeRecurring'])->name('shift-plannings.make-recurring');
    Route::post('shift-plannings/{id}/update-series', [SchedulingController::class, 'updateRecurringSeries'])->name('shift-plannings.update-series');
    Route::delete('shift-plannings/{id}/delete-series', [SchedulingController::class, 'deleteRecurringSeries'])->name('shift-plannings.delete-series');
    Route::get('shift-plannings/{id}/expand', [SchedulingController::class, 'expandRecurring'])->name('shift-plannings.expand');

    // Routes pour les doubleurs (cours simultanés)
    Route::post('shift-plannings/{id}/create-doubleur', [SchedulingController::class, 'createDoubleur'])->name('shift-plannings.create-doubleur');
    Route::get('doubleur-opportunities', [SchedulingController::class, 'doubleurOpportunities'])->name('doubleur-opportunities');
    Route::get('simultaneous-courses', [SchedulingController::class, 'simultaneousCourses'])->name('simultaneous-courses');

    // Routes pour les propositions de résolution
    Route::get('shift-plannings/{id}/proposals', [SchedulingController::class, 'getProposals'])->name('shift-plannings.proposals');
    Route::post('shift-plannings/{id}/apply-proposal', [SchedulingController::class, 'applyProposal'])->name('shift-plannings.apply-proposal');
});
