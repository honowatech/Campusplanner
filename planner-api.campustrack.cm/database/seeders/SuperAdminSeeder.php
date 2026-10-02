<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crée le super administrateur (rôle + compte).
     *
     * Le rôle `super-admin` et ses permissions sont créés par
     * `RolesAndPermissionsSeeder` ; ce seeder garantit en plus, de façon
     * défensive et idempotente, que le compte existe et dispose du contrôle
     * du mode démo.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Le super-admin est le seul à pouvoir activer/désactiver le mode démo.
        $role->givePermissionTo('demo-mode.manage');

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@campustrack.com'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@campustrack.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('super-admin');

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: superadmin@campustrack.com');
        $this->command->info('Password: password');
    }
}
