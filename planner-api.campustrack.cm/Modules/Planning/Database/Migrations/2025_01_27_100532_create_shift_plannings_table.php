<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_shift_plannings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planning_id');
            $table->unsignedBigInteger('course_class_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->date('date');
            $table->time('starting_hour');
            $table->time('ending_hour');
            $table->integer('number_teachers')->default(1);
            $table->enum('status', ['pending', 'completed', 'canceled', 'ongoing'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('planning_id')
                ->references('id')
                ->on('planning_plannings')
                ->onDelete('cascade');

            $table->index(['room_id', 'date'], 'shift_room_date_idx');
            $table->index(['teacher_id', 'date'], 'shift_teacher_date_idx');
            $table->index(['course_class_id', 'date'], 'shift_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_shift_plannings');
    }
};
