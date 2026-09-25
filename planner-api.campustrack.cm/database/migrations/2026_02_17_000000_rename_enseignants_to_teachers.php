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
        Schema::table('enseignant_matiere', function (Blueprint $table) {
            // Get the actual foreign key name and drop it
            $foreignKeys = $this->getForeignKeys('enseignant_matiere');
            foreach ($foreignKeys as $foreignKey) {
                if (str_contains($foreignKey, 'enseignant')) {
                    $table->dropForeign($foreignKey);
                }
            }
        });

        // Step 2: Rename column in pivot table
        Schema::table('enseignant_matiere', function (Blueprint $table) {
            $table->renameColumn('enseignant_id', 'teacher_id');
        });

        // Step 3: Rename tables
        Schema::rename('enseignants', 'teachers');
        Schema::rename('enseignant_matiere', 'teacher_matiere');

        // Step 4: Add new foreign keys
        Schema::table('teacher_matiere', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop foreign keys from pivot table
        Schema::table('teacher_matiere', function (Blueprint $table) {
            $foreignKeys = $this->getForeignKeys('teacher_matiere');
            foreach ($foreignKeys as $foreignKey) {
                if (str_contains($foreignKey, 'teacher')) {
                    $table->dropForeign($foreignKey);
                }
            }
        });

        // Step 2: Rename column in pivot table
        Schema::table('teacher_matiere', function (Blueprint $table) {
            $table->renameColumn('teacher_id', 'enseignant_id');
        });

        // Step 3: Rename tables back
        Schema::rename('teacher_matiere', 'enseignant_matiere');
        Schema::rename('teachers', 'enseignants');

        // Step 4: Add old foreign keys back
        Schema::table('enseignant_matiere', function (Blueprint $table) {
            $table->foreign('enseignant_id')->references('id')->on('enseignants')->onDelete('cascade');
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
