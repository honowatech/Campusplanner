<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['courses.view', 'courses.manage', 'rooms.manage'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Le super-admin récupère les nouvelles permissions
        Role::where('name', 'super-admin')->first()?->givePermissionTo($permissions);

        // L'administrateur gère salles et cours
        Role::where('name', 'administrateur')->first()?->givePermissionTo($permissions);

        // Le responsable de département gère les cours de son périmètre
        Role::where('name', 'responsable-departement')->first()?->givePermissionTo(['courses.view', 'courses.manage']);

        // Les autres rôles consultent les cours
        foreach (['personnel-administratif', 'professeur', 'etudiant'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo('courses.view');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['courses.view', 'courses.manage', 'rooms.manage'] as $name) {
            Permission::where('name', $name)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
