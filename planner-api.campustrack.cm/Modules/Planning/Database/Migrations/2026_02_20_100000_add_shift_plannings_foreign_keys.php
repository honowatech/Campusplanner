<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_shift_plannings', function (Blueprint $table) {
            $table->foreign('course_class_id', 'shift_class_fk')
                ->references('id')
                ->on('classes')
                ->onDelete('cascade');

            $table->foreign('room_id', 'shift_room_fk')
                ->references('id')
                ->on('rooms')
                ->onDelete('set null');

            $table->foreign('course_id', 'shift_course_fk')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('teacher_id', 'shift_teacher_fk')
                ->references('id')
                ->on('teachers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('planning_shift_plannings', function (Blueprint $table) {
            $table->dropForeign('shift_teacher_fk');
            $table->dropForeign('shift_course_fk');
            $table->dropForeign('shift_room_fk');
            $table->dropForeign('shift_class_fk');
        });
    }
};
