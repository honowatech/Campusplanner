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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['classroom', 'lab', 'amphitheater', 'conference', 'study_room']);
            $table->integer('capacity');
            $table->integer('floor')->nullable();
            $table->string('building')->nullable();
            $table->boolean('has_projector')->default(false);
            $table->boolean('has_computers')->default(false);
            $table->boolean('has_whiteboard')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
