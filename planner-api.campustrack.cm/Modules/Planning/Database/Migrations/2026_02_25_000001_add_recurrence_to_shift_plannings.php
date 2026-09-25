<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_shift_plannings', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('notes');
            $table->json('recurrence_pattern')->nullable()->after('is_recurring');
            $table->unsignedBigInteger('parent_id')->nullable()->after('recurrence_pattern');
            $table->foreign('parent_id')->references('id')->on('planning_shift_plannings')->onDelete('set null');
            $table->date('recurrence_end_date')->nullable()->after('parent_id');

            $table->index(['is_recurring', 'parent_id'], 'shift_recurring_idx');
        });
    }

    public function down(): void
    {
        Schema::table('planning_shift_plannings', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['is_recurring', 'recurrence_pattern', 'parent_id', 'recurrence_end_date']);
        });
    }
};
