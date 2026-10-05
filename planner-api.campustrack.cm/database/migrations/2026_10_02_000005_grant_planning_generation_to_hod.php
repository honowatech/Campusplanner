<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::where('name', 'responsable-departement')->first()?->givePermissionTo([
            'plannings.generate.auto',
            'plannings.detect.conflicts',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'responsable-departement')->first()?->revokePermissionTo([
            'plannings.generate.auto',
            'plannings.detect.conflicts',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
