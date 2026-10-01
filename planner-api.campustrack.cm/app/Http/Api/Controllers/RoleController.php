<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $roles = $query->withCount('users')->paginate($request->per_page ?? 9);

        return $this->success(['roles' => $roles], 'Liste des rôles récupérée', 200);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
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
    public function show(int $id): JsonResponse
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);

        return $this->success(['role' => $role], 'Rôle récupéré', 200);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        // Prevent modification of super-admin role by non-super-admins
        if ($role->name === 'super-admin' && ! $request->user()->hasRole('super-admin')) {
            return $this->error(null, 'Non autorisé à modifier ce rôle', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:roles,name,'.$id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

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
    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

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
    public function getPermissions(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $permissions = $role->permissions;

        return $this->success(
            ['permissions' => $permissions],
            'Permissions du rôle récupérées',
            200
        );
    }

    /**
     * Sync role permissions.
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

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
    public function getUsers(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
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
        $role = Role::findOrFail($id);

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
}
