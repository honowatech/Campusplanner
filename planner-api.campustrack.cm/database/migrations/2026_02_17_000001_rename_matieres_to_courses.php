<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Drop foreign keys from pivot table
        Schema::table('teacher_matiere', function (Blueprint $table) {
            $foreignKeys = $this->getForeignKeys('teacher_matiere');
            foreach ($foreignKeys as $foreignKey) {
                if (str_contains($foreignKey, 'matiere')) {
                    $table->dropForeign($foreignKey);
                }
            }
        });

        // Step 2: Rename column in pivot table
        Schema::table('teacher_matiere', function (Blueprint $table) {
            $table->renameColumn('matiere_id', 'course_id');
        });

        // Step 3: Rename tables
        Schema::rename('matieres', 'courses');
        Schema::rename('teacher_matiere', 'teacher_course');

        // Step 4: Add new foreign keys
        Schema::table('teacher_course', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop foreign keys from pivot table
        Schema::table('teacher_course', function (Blueprint $table) {
            $foreignKeys = $this->getForeignKeys('teacher_course');
            foreach ($foreignKeys as $foreignKey) {
                if (str_contains($foreignKey, 'course')) {
                    $table->dropForeign($foreignKey);
                }
            }
        });

        // Step 2: Rename column in pivot table
        Schema::table('teacher_course', function (Blueprint $table) {
            $table->renameColumn('course_id', 'matiere_id');
        });

        // Step 3: Rename tables back
        Schema::rename('teacher_course', 'teacher_matiere');
        Schema::rename('courses', 'matieres');

        // Step 4: Add old foreign keys back
        Schema::table('teacher_matiere', function (Blueprint $table) {
            $table->foreign('matiere_id')->references('id')->on('matieres')->onDelete('cascade');
        });
    }

    /**
     * Get foreign keys for a table
     */
    private function getForeignKeys(string $table): array
    {
        $foreignKeys = [];

        // information_schema n'existe que sur MySQL/MariaDB
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            return $foreignKeys;
        }

        $database = DB::getDatabaseName();

        $results = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$database, $table]);

        foreach ($results as $result) {
            $foreignKeys[] = $result->CONSTRAINT_NAME;
        }

        return $foreignKeys;
    }
};
