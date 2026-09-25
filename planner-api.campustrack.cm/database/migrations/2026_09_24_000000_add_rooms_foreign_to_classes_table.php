<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (tests) ne supporte pas l'ajout de FK en ALTER : la contrainte
        // n'est appliquée que sur MySQL/MariaDB.
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            // Neutraliser d'éventuelles valeurs orphelines avant la contrainte
            DB::table('classes')
                ->whereNotNull('room_id')
                ->whereNotIn('room_id', DB::table('rooms')->pluck('id'))
                ->update(['room_id' => null]);

            // La « Phase 2 » promise dans la migration de création des classes
            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });
    }
};
