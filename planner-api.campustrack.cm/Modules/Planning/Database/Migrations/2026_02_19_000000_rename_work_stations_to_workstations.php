<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('planning_work_stations', 'planning_workstations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('planning_workstations', 'planning_work_stations');
    }
};
