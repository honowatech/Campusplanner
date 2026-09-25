<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('planning_workstations', function (Blueprint $table) {
            $table->renameColumn('max_employees', 'max_teachers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planning_workstations', function (Blueprint $table) {
            $table->renameColumn('max_teachers', 'max_employees');
        });
    }
};
