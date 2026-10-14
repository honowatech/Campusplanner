<?php

use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\EnsureUserIsApproved;
use App\Http\Middleware\ProtectDemoTenant;
use App\Http\Middleware\RequireFeature;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Mode SPA : le groupe 'api' reçoit session + protection CSRF pour
        // les requêtes provenant du front (SANCTUM_STATEFUL_DOMAINS).
        $middleware->statefulApi();

        // Doit tourner APRÈS EnsureFrontendRequestsAreStateful (donc après
        // l'éventuel démarrage de session) : append le place en fin de groupe.
        // SetLocale est prependé pour s'exécuter avant les middlewares qui
        // émettent des messages (EnsureUserIsApproved, DashboardPermission…).
        $middleware->api(
            prepend: [SetLocale::class],
            append: [
                EnsureUserIsApproved::class,
                ResolveTenant::class,
                EnsureTenantIsActive::class,
                ProtectDemoTenant::class,
            ],
        );

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'feature' => RequireFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Rend les erreurs de validation (422) dans le format attendu par le
        // front ({ status, message, data }) au lieu du format Laravel par défaut
        // ({ message, errors }), et localisées via lang/{locale}/validation.php.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'data' => $e->errors(),
                ], 422);
            }
        });
    })->create();
