<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'professeur')->first();

        if ($role) {
            // L'enseignant consulte désormais les plannings par matière
            // (cours qu'il enseigne) plutôt que par classe.
            $role->revokePermissionTo('plannings.view.class');
            $role->givePermissionTo('plannings.view.subject');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', 'professeur')->first();

        if ($role) {
            $role->revokePermissionTo('plannings.view.subject');
            $role->givePermissionTo('plannings.view.class');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
