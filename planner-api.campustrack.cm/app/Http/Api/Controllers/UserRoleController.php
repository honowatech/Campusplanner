<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    use HttpResponses;

    /**
     * Rôles d'administration : seul un super-admin peut les attribuer/retirer,
     * sinon un administrateur pourrait s'auto-promouvoir super-admin.
     */
    private const RESTRICTED_ROLES = ['super-admin', 'administrateur'];

    /**
     * Get user's roles.
     */
    public function getRoles(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $roles = $user->roles;

        return $this->success(
            ['roles' => $roles],
            'Rôles de l\'utilisateur récupérés',
            200
        );
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'mode' => 'in:add,sync',
        ]);

        if (array_intersect($validated['roles'], self::RESTRICTED_ROLES)
            && ! $request->user()->hasRole('super-admin')) {
            return $this->error(
                null,
                'Seul un super-admin peut gérer les rôles : '.implode(', ', self::RESTRICTED_ROLES),
                403
            );
        }

        $mode = $validated['mode'] ?? 'add';

        if ($mode === 'sync') {
            $user->syncRoles($validated['roles']);
            $message = 'Rôles synchronisés avec succès';
        } else {
            $user->assignRole($validated['roles']);
            $message = 'Rôles attribués avec succès';
        }

        return $this->success(
            ['roles' => $user->roles()->pluck('name')],
            $message,
            200
        );
    }

    /**
     * Remove roles from user.
     */
    public function removeRoles(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if (array_intersect($validated['roles'], self::RESTRICTED_ROLES)
            && ! $request->user()->hasRole('super-admin')) {
            return $this->error(
                null,
                'Seul un super-admin peut gérer les rôles : '.implode(', ', self::RESTRICTED_ROLES),
                403
            );
        }

        $user->removeRole($validated['roles']);

        return $this->success(
            ['roles' => $user->roles()->pluck('name')],
            'Rôles retirés avec succès',
            200
        );
    }

    /**
     * Sync user roles (replace all).
     */
    public function syncRoles(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if (array_intersect($validated['roles'], self::RESTRICTED_ROLES)
            && ! $request->user()->hasRole('super-admin')) {
            return $this->error(
                null,
                'Seul un super-admin peut gérer les rôles : '.implode(', ', self::RESTRICTED_ROLES),
                403
            );
        }

        $user->syncRoles($validated['roles']);

        return $this->success(
            ['roles' => $user->roles()->pluck('name')],
            'Rôles synchronisés avec succès',
            200
        );
    }

    /**
     * Get user's direct permissions.
     */
    public function getPermissions(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $permissions = $user->permissions;

        return $this->success(
            ['permissions' => $permissions],
            'Permissions directes de l\'utilisateur récupérées',
            200
        );
    }

    /**
     * Get all user permissions (including from roles).
     */
    public function getAllPermissions(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $permissions = $user->getAllPermissions();

        return $this->success(
            ['permissions' => $permissions],
            'Toutes les permissions de l\'utilisateur récupérées',
            200
        );
    }

    /**
     * Give direct permissions to user.
     */
    public function givePermissions(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $user->givePermissionTo($validated['permissions']);

        return $this->success(
            ['permissions' => $user->permissions()->pluck('name')],
            'Permissions attribuées avec succès',
            200
        );
    }

    /**
     * Revoke direct permissions from user.
     */
    public function revokePermissions(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $user->revokePermissionTo($validated['permissions']);

        return $this->success(
            ['permissions' => $user->permissions()->pluck('name')],
            'Permissions révoquées avec succès',
            200
        );
    }

    /**
     * Check if user has a specific permission.
     */
    public function checkPermission(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $hasPermission = $user->hasPermissionTo($validated['permission']);

        return $this->success(
            [
                'has_permission' => $hasPermission,
                'permission' => $validated['permission'],
            ],
            'Vérification de permission effectuée',
            200
        );
    }
}
