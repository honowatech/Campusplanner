<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the permissions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->has('resource')) {
            $query->where('name', 'like', $request->resource.'.%');
        }

        $permissions = $query->withCount('roles')->paginate($request->per_page ?? 9);

        return $this->success(
            ['permissions' => $permissions],
            'Liste des permissions récupérée',
            200
        );
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'resource' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'scope' => 'nullable|string|max:50',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return $this->success(
            ['permission' => $permission],
            'Permission créée avec succès',
            201
        );
    }

    /**
     * Display the specified permission.
     */
    public function show(int $id): JsonResponse
    {
        $permission = Permission::with('roles')->findOrFail($id);

        return $this->success(
            ['permission' => $permission],
            'Permission récupérée',
            200
        );
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:permissions,name,'.$id,
        ]);

        if (isset($validated['name'])) {
            $permission->name = $validated['name'];
            $permission->save();
        }

        return $this->success(
            ['permission' => $permission],
            'Permission mise à jour avec succès',
            200
        );
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        // Check if permission is used by roles
        if ($permission->roles()->count() > 0) {
            return $this->error(
                null,
                'Cette permission est utilisée par des rôles. Veuillez d\'abord la retirer des rôles.',
                400
            );
        }

        $permission->delete();

        return $this->success(null, 'Permission supprimée avec succès', 200);
    }

    /**
     * Group permissions by resource.
     */
    public function byResource(): JsonResponse
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $resource = $parts[0];

            if (! isset($grouped[$resource])) {
                $grouped[$resource] = [];
            }

            $grouped[$resource][] = $permission;
        }

        return $this->success(
            ['permissions_by_resource' => $grouped],
            'Permissions groupées par ressource',
            200
        );
    }

    /**
     * Get roles with this permission.
     */
    public function getRoles(int $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $roles = $permission->roles;

        return $this->success(
            ['roles' => $roles],
            'Rôles avec cette permission récupérés',
            200
        );
    }
}
