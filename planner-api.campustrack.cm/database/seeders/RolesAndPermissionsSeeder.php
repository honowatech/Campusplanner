<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Tenant;
use App\Support\RoleCatalog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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
        RoleCatalog::seedPermissions();

        $this->command->info('Permissions created successfully!');
    }

    /**
     * Create roles with their permissions
     */
    private function createRoles(): void
    {
        RoleCatalog::seedGlobalRole();

        $demo = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => 'Démo CampusTrack', 'status' => 'active', 'is_demo' => true]
        );

        RoleCatalog::seedTenantRoles($demo->id);

        $this->command->info('Roles created and permissions assigned successfully!');
    }

}
