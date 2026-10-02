<?php

use App\Http\Api\Controllers\AiController;
use App\Http\Api\Controllers\AuthController;
use App\Http\Api\Controllers\CourseClassController;
use App\Http\Api\Controllers\CourseController;
use App\Http\Api\Controllers\DashboardController;
use App\Http\Api\Controllers\DemoModeController;
use App\Http\Api\Controllers\DepartmentController;
use App\Http\Api\Controllers\PermissionController;
use App\Http\Api\Controllers\RoleController;
use App\Http\Api\Controllers\RoomBlockingController;
use App\Http\Api\Controllers\RoomController;
use App\Http\Api\Controllers\StudentController;
use App\Http\Api\Controllers\TeacherBlockingController;
use App\Http\Api\Controllers\TeacherController;
use App\Http\Api\Controllers\UserController;
use App\Http\Api\Controllers\UserRoleController;
use App\Http\Middleware\DashboardPermission;
use Illuminate\Support\Facades\Route;

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

// Auth routes
// Le groupe 'api' est rendu stateful par bootstrap/app.php (statefulApi()) :
// les requêtes du front (Origin/Referer listé dans SANCTUM_STATEFUL_DOMAINS)
// disposent donc d'une session cookie + de la protection CSRF Sanctum.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/auth-user', [AuthController::class, 'authUser']);
});

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

// Mode démo : état + connexion sans mot de passe (public, nécessaire pour la
// page de connexion non authentifiée). L'activation/désactivation est réservée
// au super-admin (route protégée plus bas).
Route::get('/demo-mode', [DemoModeController::class, 'index']);
Route::post('/demo-login', [DemoModeController::class, 'login'])->middleware('throttle:10,1');

// Protected API routes
Route::middleware(['auth:sanctum'])->group(function () {

    // === PROXY IA (la clé Gemini reste côté serveur) ===
    Route::post('/ai/generate', [AiController::class, 'generate'])
        ->middleware('throttle:10,1');

    // === MODE DÉMO (Super Admin uniquement) ===
    Route::middleware(['role:super-admin'])->group(function () {
        Route::put('/demo-mode', [DemoModeController::class, 'update']);
    });

    // === GESTION DES RÔLES (Super Admin & Admin uniquement) ===
    Route::middleware(['role:super-admin|administrateur'])->group(function () {
        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::get('roles/{id}', [RoleController::class, 'show']);
        Route::put('roles/{id}', [RoleController::class, 'update']);
        Route::delete('roles/{id}', [RoleController::class, 'destroy']);
        Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
        Route::post('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);
        Route::get('roles/{id}/users', [RoleController::class, 'getUsers']);
        Route::post('roles/{id}/users', [RoleController::class, 'assignUsers']);
    });

    // === GESTION DES PERMISSIONS (Super Admin & Admin uniquement) ===
    Route::middleware(['role:super-admin|administrateur'])->group(function () {
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::post('permissions', [PermissionController::class, 'store']);
        Route::get('permissions/by-resource', [PermissionController::class, 'byResource']);
        Route::get('permissions/{id}', [PermissionController::class, 'show']);
        Route::put('permissions/{id}', [PermissionController::class, 'update']);
        Route::delete('permissions/{id}', [PermissionController::class, 'destroy']);
        Route::get('permissions/{id}/roles', [PermissionController::class, 'getRoles']);
    });

    // === GESTION DES DÉPARTEMENTS ===
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::post('departments', [DepartmentController::class, 'store'])->middleware('role:super-admin|administrateur');
    Route::get('departments/{id}', [DepartmentController::class, 'show']);
    Route::put('departments/{id}', [DepartmentController::class, 'update'])->middleware('role:super-admin|administrateur');
    Route::delete('departments/{id}', [DepartmentController::class, 'destroy'])->middleware('role:super-admin|administrateur');
    Route::get('departments/{id}/users', [DepartmentController::class, 'getUsers']);
    Route::get('departments/{id}/stats', [DepartmentController::class, 'getStats']);

    // === GESTION DES RÔLES/PERMISSIONS UTILISATEUR ===
    Route::prefix('users/{id}')->group(function () {
        // Rôles
        Route::get('/roles', [UserRoleController::class, 'getRoles']);
        Route::post('/roles', [UserRoleController::class, 'assignRoles'])->middleware('role:super-admin|administrateur');
        Route::delete('/roles', [UserRoleController::class, 'removeRoles'])->middleware('role:super-admin|administrateur');
        Route::put('/roles/sync', [UserRoleController::class, 'syncRoles'])->middleware('role:super-admin|administrateur');

        // Permissions
        Route::get('/permissions', [UserRoleController::class, 'getPermissions']);
        Route::get('/all-permissions', [UserRoleController::class, 'getAllPermissions']);
        Route::post('/permissions', [UserRoleController::class, 'givePermissions'])->middleware('role:super-admin|administrateur');
        Route::delete('/permissions', [UserRoleController::class, 'revokePermissions'])->middleware('role:super-admin|administrateur');

        // Vérification
        Route::post('/check-permission', [UserRoleController::class, 'checkPermission']);
    });

    // === GESTION DES UTILISATEURS (Super Admin & Admin uniquement) ===
    Route::middleware(['role:super-admin|administrateur'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/pending', [UserController::class, 'pending']);
        Route::post('/users/{id}/approve', [UserController::class, 'approve']);
        Route::post('/users/{id}/reject', [UserController::class, 'reject']);
        Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('role:super-admin');
    });

    // === RESSOURCES : COURSES ===
    Route::apiResource('courses', CourseController::class);
    Route::get('courses/{id}/teachers', [CourseController::class, 'getTeachers']);

    // === RESSOURCES : TEACHERS ===
    Route::apiResource('teachers', TeacherController::class);
    Route::post('teachers/{id}/courses', [TeacherController::class, 'syncCourses']);
    Route::get('teachers/{id}/schedule', [TeacherController::class, 'getSchedule']);
    Route::post('teachers/{id}/check-availability', [TeacherController::class, 'checkAvailability']);
    Route::post('teachers/search-available', [TeacherController::class, 'searchAvailable']);

    // === RESSOURCES : CLASSES ===
    Route::apiResource('classes', CourseClassController::class);
    Route::get('classes/{id}/students', [CourseClassController::class, 'getStudents']);
    Route::post('classes/{id}/students', [CourseClassController::class, 'addStudents']);
    Route::get('classes/{id}/schedule', [CourseClassController::class, 'getSchedule']);
    Route::get('classes/{id}/teachers', [CourseClassController::class, 'getTeachers']);

    // === RESSOURCES : STUDENTS ===
    Route::apiResource('students', StudentController::class);
    Route::get('students/{id}/schedule', [StudentController::class, 'getSchedule']);
    Route::put('students/{id}/move-to-class', [StudentController::class, 'moveToClass']);
    Route::get('students/unassigned', [StudentController::class, 'getUnassigned']);

    // === RESSOURCES : ROOMS ===
    Route::apiResource('rooms', RoomController::class);
    Route::get('rooms/{id}/blockings', [RoomController::class, 'getBlockings']);
    Route::get('rooms/{id}/schedule', [RoomController::class, 'getSchedule']);
    Route::post('rooms/{id}/check-availability', [RoomController::class, 'checkAvailability']);
    Route::post('rooms/search-available', [RoomController::class, 'searchAvailable']);

    // === RESSOURCES : ROOM BLOCKINGS ===
    Route::apiResource('room-blockings', RoomBlockingController::class);
    Route::get('room-blockings/room/{roomId}', [RoomBlockingController::class, 'getByRoom']);
    Route::post('room-blockings/check-conflicts', [RoomBlockingController::class, 'checkConflicts']);

    // === RESSOURCES : TEACHER BLOCKINGS ===
    Route::apiResource('teacher-blockings', TeacherBlockingController::class);
    Route::get('teacher-blockings/pending', [TeacherBlockingController::class, 'pending']);
    Route::put('teacher-blockings/{id}/approve', [TeacherBlockingController::class, 'approve']);
    Route::put('teacher-blockings/{id}/reject', [TeacherBlockingController::class, 'reject']);
    Route::get('teacher-blockings/teacher/{teacherId}', [TeacherBlockingController::class, 'getByTeacher']);
    Route::get('teacher-blockings/my-blockings', [TeacherBlockingController::class, 'myBlockings']);
    Route::post('teacher-blockings/check-availability', [TeacherBlockingController::class, 'checkAvailability']);

    // === TABLEAU DE BORD ADMINISTRATEUR ===
    Route::middleware(['role:super-admin|administrateur', DashboardPermission::class])->prefix('dashboard')->group(function () {

        // Main endpoints
        Route::get('/overview', [DashboardController::class, 'overview']);
        Route::get('/departments', [DashboardController::class, 'departments']);
        Route::get('/resources', [DashboardController::class, 'resources']);
        Route::get('/alerts', [DashboardController::class, 'alerts']);
        Route::get('/activity', [DashboardController::class, 'activity']);
        Route::get('/charts', [DashboardController::class, 'charts']);

        // Admin-only: refresh cache
        Route::post('/refresh-cache', [DashboardController::class, 'refreshCache'])
            ->middleware('role:super-admin');
    });
});
