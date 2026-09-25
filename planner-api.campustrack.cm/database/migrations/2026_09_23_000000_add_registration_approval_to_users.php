<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rôle demandé lors de l'inscription self-service
            $table->string('requested_role')->nullable()->after('department_id');
            // Compte validé par un administrateur ?
            // Default true : les comptes existants (seedés/créés par un admin) restent actifs ;
            // l'inscription publique positionne explicitement false.
            $table->boolean('is_approved')->default(true)->after('requested_role');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete()
                ->after('is_approved');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['requested_role', 'is_approved', 'approved_at']);
        });
    }
};
