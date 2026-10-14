<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Support\RoleCatalog;
use App\Traits\HttpResponses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use HttpResponses;

    /**
     * Requête de permissions scopée au rôle de l'appelant.
     *
     * Les permissions sont GLOBALES (pas de tenant_id) : elles ne sont donc pas
     * isolées par école, mais les permissions « plateforme » (roles.manage,
     * permissions.manage, tenants.manage, …) sont masquées aux admins de tenant
     * afin qu'ils ne puissent ni les voir ni les manipuler.
     */
    private function scopedQuery(Request $request): Builder
    {
        $query = Permission::query();

        if (! $request->user()?->hasRole('super-admin')) {
            $query->whereNotIn('name', RoleCatalog::PLATFORM_PERMISSIONS);
        }

        return $query;
    }

    /**
     * Refuse qu'un admin de tenant manipule une permission réservée plateforme.
     */
    private function ensureNotPlatformPermission(Request $request, string $name): void
    {
        if ($request->user()?->hasRole('super-admin')) {
            return;
        }

        if (in_array($name, RoleCatalog::PLATFORM_PERMISSIONS, true)) {
            abort(403, __('resources.permissions.platform_reserved'));
        }
    }

    /**
     * Display a listing of the permissions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->scopedQuery($request);

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->has('resource')) {
            $query->where('name', 'like', $request->resource.'.%');
        }

        $permissions = $query->withCount('roles')->paginate($request->per_page ?? 9);

        return $this->success(
            ['permissions' => $permissions],
            __('resources.permissions.list'),
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

        $this->ensureNotPlatformPermission($request, $validated['name']);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return $this->success(
            ['permission' => $permission],
            __('resources.permissions.created'),
            201
        );
    }

    /**
     * Display the specified permission.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $permission = $this->scopedQuery($request)->with('roles')->findOrFail($id);

        return $this->success(
            ['permission' => $permission],
            __('resources.permissions.retrieved'),
            200
        );
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $permission = $this->scopedQuery($request)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:permissions,name,'.$id,
        ]);

        if (isset($validated['name'])) {
            $this->ensureNotPlatformPermission($request, $validated['name']);
            $permission->name = $validated['name'];
            $permission->save();
        }

        return $this->success(
            ['permission' => $permission],
            __('resources.permissions.updated'),
            200
        );
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $permission = $this->scopedQuery($request)->findOrFail($id);

        $this->ensureNotPlatformPermission($request, $permission->name);

        // Check if permission is used by roles
        if ($permission->roles()->count() > 0) {
            return $this->error(
                null,
                __('resources.permissions.cannot_delete_with_roles'),
                400
            );
        }

        $permission->delete();

        return $this->success(null, __('resources.permissions.deleted'), 200);
    }

    /**
     * Group permissions by resource.
     */
    public function byResource(Request $request): JsonResponse
    {
        $permissions = $this->scopedQuery($request)->get();
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
            __('resources.permissions.grouped_by_resource'),
            200
        );
    }

    /**
     * Get roles with this permission.
     */
    public function getRoles(Request $request, int $id): JsonResponse
    {
        $permission = $this->scopedQuery($request)->findOrFail($id);
        $roles = $permission->roles;

        return $this->success(
            ['roles' => $roles],
            __('resources.permissions.roles_retrieved'),
            200
        );
    }
}
