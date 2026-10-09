<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RoleCatalog;
use App\Traits\HttpResponses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use HttpResponses;

    /**
     * Requête de rôles scopée au tenant courant.
     *
     * - Admin de tenant (tenant_id non nul) : uniquement les rôles de son école.
     * - Super-admin (global) : tous les rôles.
     */
    private function scopedQuery(Request $request): Builder
    {
        $query = Role::query();

        $tenantId = $request->user()?->tenant_id;

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->scopedQuery($request);

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $roles = $query->paginate($request->per_page ?? 9);

        // `Role::users()` résout le modèle via getModelForGuard(default guard).
        // Le middleware `auth:sanctum` pose `sanctum` en guard par défaut, qui
        // ne mappe aucun provider : on compte donc directement dans la table
        // pivot model_has_roles au lieu d'utiliser withCount('users').
        $this->attachUsersCount($roles);

        return $this->success(['roles' => $roles], 'Liste des rôles récupérée', 200);
    }

    /**
     * Renseigne users_count sur chaque rôle à partir de la table pivot.
     */
    private function attachUsersCount($roles): void
    {
        if ($roles->isEmpty()) {
            return;
        }

        $pivotTable = config('permission.table_names.model_has_roles');
        $pivotRole = config('permission.column_names.role_pivot_key') ?? 'role_id';

        $counts = \DB::table($pivotTable)
            ->whereIn($pivotRole, $roles->pluck('id')->all())
            ->groupBy($pivotRole)
            ->selectRaw($pivotRole.' as role_id, count(*) as users_count')
            ->pluck('users_count', 'role_id');

        $roles->getCollection()->transform(function ($role) use ($counts) {
            $role->users_count = (int) ($counts[$role->id] ?? 0);

            return $role;
        });
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $permissions = $validated['permissions'] ?? [];

        $this->ensureNoPlatformPermissions($request, $permissions);

        // Le nom doit être unique au sein du tenant (le `unique:roles,name` global
        // serait trop restrictif avec plusieurs écoles).
        if ($this->scopedQuery($request)->where('name', $validated['name'])->exists()) {
            return $this->error(null, 'Un rôle portant ce nom existe déjà.', 422);
        }

        // `Role::create` (spatie teams) rattache le rôle au tenant courant.
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $this->success(
            ['role' => $role->load('permissions')],
            'Rôle créé avec succès',
            201
        );
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->with(['permissions', 'users'])->findOrFail($id);

        return $this->success(['role' => $role], 'Rôle récupéré', 200);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->findOrFail($id);

        // Prevent modification of super-admin role by non-super-admins
        if ($role->name === 'super-admin' && ! $request->user()->hasRole('super-admin')) {
            return $this->error(null, 'Non autorisé à modifier ce rôle', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if (isset($validated['permissions'])) {
            $this->ensureNoPlatformPermissions($request, $validated['permissions']);
        }

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
        }

        $role->save();

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return $this->success(
            ['role' => $role->load('permissions')],
            'Rôle mis à jour avec succès',
            200
        );
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->findOrFail($id);

        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return $this->error(null, 'Le rôle super-admin ne peut pas être supprimé', 403);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return $this->error(
                null,
                'Ce rôle est attribué à des utilisateurs. Veuillez d\'abord retirer ce rôle des utilisateurs.',
                400
            );
        }

        $role->delete();

        return $this->success(null, 'Rôle supprimé avec succès', 200);
    }

    /**
     * Get role permissions.
     */
    public function getPermissions(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->findOrFail($id);

        return $this->success(
            ['permissions' => $role->permissions],
            'Permissions du rôle récupérées',
            200
        );
    }

    /**
     * Sync role permissions.
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $this->ensureNoPlatformPermissions($request, $validated['permissions']);

        $role->syncPermissions($validated['permissions']);

        return $this->success(
            ['role' => $role->load('permissions')],
            'Permissions synchronisées avec succès',
            200
        );
    }

    /**
     * Get users with this role.
     */
    public function getUsers(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->findOrFail($id);

        $users = $role->users()->paginate(15);

        return $this->success(
            ['users' => $users],
            'Utilisateurs avec ce rôle récupérés',
            200
        );
    }

    /**
     * Assign role to multiple users.
     */
    public function assignUsers(Request $request, int $id): JsonResponse
    {
        $role = $this->scopedQuery($request)->findOrFail($id);

        if (in_array($role->name, ['super-admin', 'administrateur'], true)
            && ! $request->user()->hasRole('super-admin')) {
            return $this->error(null, 'Seul un super-admin peut attribuer ce rôle.', 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        return $this->success(
            ['assigned_count' => count($validated['user_ids'])],
            'Rôle attribué aux utilisateurs avec succès',
            200
        );
    }

    /**
     * Refuse qu'un admin de tenant distribue une permission réservée à la
     * plateforme (anti auto-promotion).
     */
    private function ensureNoPlatformPermissions(Request $request, array $permissions): void
    {
        if ($request->user()->hasRole('super-admin')) {
            return;
        }

        $forbidden = array_intersect($permissions, RoleCatalog::PLATFORM_PERMISSIONS);

        if ($forbidden !== []) {
            abort(403, 'Permissions réservées à la plateforme : '.implode(', ', $forbidden));
        }
    }
}
