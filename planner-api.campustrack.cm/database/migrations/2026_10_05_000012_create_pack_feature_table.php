<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lien many-to-many entre packs et fonctionnalités.
     */
    public function up(): void
    {
        Schema::create('pack_feature', function (Blueprint $table) {
            $table->foreignId('pack_id')->constrained('packs')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->primary(['pack_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_feature');
    }
};
