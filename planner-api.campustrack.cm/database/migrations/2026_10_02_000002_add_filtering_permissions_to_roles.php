<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::where('name', 'responsable-departement')->first()?->givePermissionTo([
            'departments.view',
            'rooms.view',
            'rooms.search',
        ]);

        Role::where('name', 'personnel-administratif')->first()?->givePermissionTo([
            'departments.view',
            'teachers.view.department',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'responsable-departement')->first()?->revokePermissionTo([
            'departments.view',
            'rooms.view',
            'rooms.search',
        ]);

        Role::where('name', 'personnel-administratif')->first()?->revokePermissionTo([
            'departments.view',
            'teachers.view.department',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
