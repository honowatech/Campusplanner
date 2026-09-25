<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('class_id', 'course_class_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('course_class_id', 'students_course_class_fk')
                ->references('id')
                ->on('classes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['course_class_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('course_class_id', 'class_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_id')
                ->references('id')
                ->on('classes')
                ->nullOnDelete();
        });
    }
};
