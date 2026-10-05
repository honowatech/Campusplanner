<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create departments first
        $this->createDepartments();

        // Create permissions
        $this->createPermissions();

        // Create roles
        $this->createRoles();

        // Le super administrateur est créé par le seeder dédié SuperAdminSeeder.
    }

    /**
     * Create default departments
     */
    private function createDepartments(): void
    {
        $departments = [
            [
                'name' => 'Informatique',
                'code' => 'INFO',
                'description' => 'Département d\'informatique et technologies',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Mathématiques',
                'code' => 'MATH',
                'description' => 'Département de mathématiques',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Physique',
                'code' => 'PHYS',
                'description' => 'Département de physique',
                'color' => '#10B981',
            ],
            [
                'name' => 'Langues',
                'code' => 'LANG',
                'description' => 'Département des langues étrangères',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Sciences Économiques',
                'code' => 'ECO',
                'description' => 'Département des sciences économiques',
                'color' => '#8B5CF6',
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        $this->command->info('Departments created successfully!');
    }

    /**
     * Create all permissions
     */
    private function createPermissions(): void
    {
        $permissions = [
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
            'courses.view',
            'courses.manage',
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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('Permissions created successfully!');
    }

    /**
     * Create roles with their permissions
     */
    private function createRoles(): void
    {
        // Super Admin - All permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Administrateur - Almost all except super-admin management
        $admin = Role::firstOrCreate(['name' => 'administrateur', 'guard_name' => 'web']);
        $adminPermissions = Permission::whereNotIn('name', [
            'roles.manage',  // Only super-admin can manage roles
            'permissions.manage',  // Only super-admin can manage permissions
        ])->get();
        $admin->syncPermissions($adminPermissions);

        // Responsable de Département - Department scoped permissions
        $responsable = Role::firstOrCreate(['name' => 'responsable-departement', 'guard_name' => 'web']);
        $responsablePermissions = [
            'users.view.department',
            // Filtres/listes de référence : départements
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
            // Gestion des matières de son département
            'courses.view',
            'courses.manage',
            // Filtres/listes de référence : salles
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
        ];
        $responsable->syncPermissions($responsablePermissions);

        // Personnel Administratif - Limited permissions
        $personnel = Role::firstOrCreate(['name' => 'personnel-administratif', 'guard_name' => 'web']);
        $personnelPermissions = [
            'users.view.department',
            // Filtres/listes de référence : départements
            'departments.view',
            'plannings.view.department',
            'students.view.department',
            // Filtres/listes de référence : enseignants
            'teachers.view.department',
            'classes.view',
            'rooms.view',
            'rooms.search',
            'courses.view',
            'export.pdf',
            'export.excel',
            'reports.view',
            'dashboard.view',
        ];
        $personnel->syncPermissions($personnelPermissions);

        // Professeur - Teacher permissions
        $professeur = Role::firstOrCreate(['name' => 'professeur', 'guard_name' => 'web']);
        $professeurPermissions = [
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
        ];
        $professeur->syncPermissions($professeurPermissions);

        // Étudiant - View only
        $etudiant = Role::firstOrCreate(['name' => 'etudiant', 'guard_name' => 'web']);
        $etudiantPermissions = [
            'courses.view',
            'plannings.view.class',
            'grades.view.class',
            'bulletins.view',
            'calendar.view',
            'dashboard.view',
        ];
        $etudiant->syncPermissions($etudiantPermissions);

        $this->command->info('Roles created and permissions assigned successfully!');
    }

}
