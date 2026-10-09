<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables métier portant la colonne `tenant_id`.
     *
     * `users` conserve un `tenant_id` NULL pour les comptes globaux (super-admin).
     * `app_settings` reste volontairement hors de cette liste : singleton plateforme.
     *
     * @return list<string>
     */
    private function tables(): array
    {
        return [
            'users',
            'departments',
            'courses',
            'teachers',
            'teacher_course',
            'classes',
            'students',
            'rooms',
            'room_blockings',
            'teacher_blockings',
            'planning_plannings',
            'planning_shift_plannings',
            'planning_workstations',
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->constrained('tenants')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables()) as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
