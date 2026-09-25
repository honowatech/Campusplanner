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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('level'); // e.g., "6th", "5th", "Final"
            $table->integer('capacity');
            $table->unsignedBigInteger('room_id')->nullable(); // Foreign key to rooms table will be added in Phase 2
            $table->string('academic_year'); // e.g., "2024-2025"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
