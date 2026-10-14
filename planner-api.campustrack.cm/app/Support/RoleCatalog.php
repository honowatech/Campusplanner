<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Catalogue des rôles par défaut et de leurs permissions.
 *
 * - `super-admin` est un rôle GLOBAL (tenant_id NULL), réservé à la plateforme.
 * - Les autres rôles sont des rôles de TENANT, semés par école via
 *   `seedTenantRoles()` (spatie « teams », team_id = tenant_id).
 */
class RoleCatalog
{
    public const GLOBAL_ROLE = 'super-admin';

    /**
     * Rôles semés au sein de chaque tenant.
     */
    public const TENANT_ROLES = [
        'administrateur',
        'responsable-departement',
        'personnel-administratif',
        'professeur',
        'etudiant',
    ];

    /**
     * Permissions réservées à la plateforme : un admin de tenant ne peut pas
     * les créer ni les distribuer (anti auto-promotion).
     */
    public const PLATFORM_PERMISSIONS = [
        'roles.manage',
        'permissions.manage',
        'tenants.manage',
        'payments.manage',
        'demo-mode.manage',
    ];

    /**
     * Liste complète des permissions (noms).
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            // User management
            'users.view.all',
            'users.view.department',
            'users.create',
            'users.edit',
            'users.delete',

            // Role management
            'roles.manage',

            // Permission management
            'permissions.manage',

            // Department management
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // Planning management - Global
            'plannings.view.all',
            'plannings.create.all',
            'plannings.edit.all',
            'plannings.delete.all',

            // Planning management - Department scope
            'plannings.view.department',
            'plannings.create.department',
            'plannings.edit.department',
            'plannings.delete.department',

            // Planning management - Class scope
            'plannings.view.class',
            'plannings.create.class',
            'plannings.edit.class',

            // Planning management - Subject scope
            'plannings.view.subject',
            'plannings.create.subject',
            'plannings.edit.subject',

            // Special planning actions
            'plannings.generate.auto',
            'plannings.detect.conflicts',

            // Course management (matières)
            'courses.view',
            'courses.manage',

            // Room management (CRUD, au-delà de la consultation/blocage)
            'rooms.manage',

            // Teacher management
            'teachers.view.all',
            'teachers.view.department',
            'teachers.manage.department',
            'teachers.block',

            // Student management
            'students.view.all',
            'students.view.department',
            'students.view.class',
            'students.create',
            'students.edit',
            'students.delete',

            // Class management
            'classes.view',
            'classes.manage',

            // Room management
            'rooms.view',
            'rooms.block',
            'rooms.search',

            // Grade management
            'grades.view.all',
            'grades.view.department',
            'grades.view.class',
            'grades.view.subject',
            'grades.edit',

            // Bulletin management
            'bulletins.view',
            'bulletins.create',
            'bulletins.edit',
            'bulletins.delete',

            // Event management
            'events.view',
            'events.create',
            'events.edit',
            'events.delete',
            'events.cancel',
            'events.postpone',

            // Calendar management
            'calendar.view',
            'calendar.manage',

            // Notification management
            'notifications.send',
            'notifications.configure',

            // Export/Import
            'export.pdf',
            'export.excel',
            'import.csv',

            // Reports
            'reports.view',
            'reports.generate',

            // Settings
            'settings.view',
            'settings.edit',

            // Demo mode (super-admin uniquement)
            'demo-mode.manage',

            // History
            'history.view',

            // Dashboard
            'dashboard.view',

            // SMS
            'sms.send',
        ];
    }

    /**
     * Crée les permissions (globales, non scopées par tenant).
     */
    public static function seedPermissions(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    /**
     * Mapping rôle de tenant => permissions (noms).
     *
     * @return array<string, list<string>>
     */
    public static function tenantRolePermissions(): array
    {
        return [
            'administrateur' => Permission::whereNotIn('name', self::PLATFORM_PERMISSIONS)
                ->pluck('name')
                ->all(),

            'responsable-departement' => [
                'users.view.department',
                'departments.view',
                'plannings.view.department',
                'plannings.create.department',
                'plannings.edit.department',
                'plannings.delete.department',
                'plannings.generate.auto',
                'plannings.detect.conflicts',
                'teachers.view.department',
                'teachers.manage.department',
                'students.view.department',
                'classes.view',
                'courses.view',
                'courses.manage',
                'rooms.view',
                'rooms.search',
                'grades.view.department',
                'bulletins.view',
                'events.view',
                'events.create',
                'events.edit',
                'calendar.view',
                'notifications.send',
                'export.pdf',
                'export.excel',
                'reports.view',
                'reports.generate',
                'dashboard.view',
            ],

            'personnel-administratif' => [
                'users.view.department',
                'departments.view',
                'plannings.view.department',
                'students.view.department',
                'teachers.view.department',
                'classes.view',
                'rooms.view',
                'rooms.search',
                'courses.view',
                'export.pdf',
                'export.excel',
                'reports.view',
                'dashboard.view',
            ],

            'professeur' => [
                'courses.view',
                'plannings.view.subject',
                'plannings.create.class',
                'plannings.edit.class',
                'students.view.class',
                'grades.view.class',
                'grades.view.subject',
                'grades.edit',
                'bulletins.view',
                'events.view',
                'calendar.view',
                'export.pdf',
                'dashboard.view',
            ],

            'etudiant' => [
                'courses.view',
                'plannings.view.class',
                'grades.view.class',
                'bulletins.view',
                'calendar.view',
                'dashboard.view',
            ],
        ];
    }

    /**
     * Crée le rôle global `super-admin` (toutes les permissions).
     */
    public static function seedGlobalRole(): void
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId(null);

        $superAdmin = Role::findOrCreate(self::GLOBAL_ROLE, 'web');
        $superAdmin->syncPermissions(Permission::all());

        setPermissionsTeamId($previous);
    }

    /**
     * Crée les rôles de tenant pour une école donnée.
     */
    public static function seedTenantRoles(int $tenantId): void
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId($tenantId);

        foreach (self::tenantRolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        setPermissionsTeamId($previous);
    }
}
