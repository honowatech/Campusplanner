<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_EMAIL = 'superadmin@campustrack.com';

    private const NEW_EMAIL = 'admin@focusrent.cm';

    /**
     * Le compte super-admin utilise désormais admin@focusrent.cm, adresse qui
     * reçoit ses liens de réinitialisation du mot de passe.
     *
     * Sur une base existante : renomme l'ancien compte seedé, ou à défaut
     * l'unique compte super-admin. Ne fait rien si l'adresse est déjà prise
     * ou si plusieurs super-admins rendent le choix ambigu.
     */
    public function up(): void
    {
        if (DB::table('users')->where('email', self::NEW_EMAIL)->exists()) {
            return;
        }

        $id = DB::table('users')->where('email', self::OLD_EMAIL)->value('id');

        if (! $id) {
            $superAdmins = DB::table('users')
                ->join('model_has_roles', function ($join) {
                    $join->on('model_has_roles.model_id', '=', 'users.id')
                        ->where('model_has_roles.model_type', 'App\\Models\\User');
                })
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'super-admin')
                ->distinct()
                ->pluck('users.id');

            $id = $superAdmins->count() === 1 ? $superAdmins->first() : null;
        }

        if ($id) {
            DB::table('users')->where('id', $id)->update(['email' => self::NEW_EMAIL]);
        }
    }

    public function down(): void
    {
        // Irréversible sans connaître l'adresse d'origine.
    }
};
