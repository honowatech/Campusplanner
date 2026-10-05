<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->integer('max_weekly_hours_per_teacher')->default(18);
            $table->integer('max_consecutive_hours')->default(4);
            $table->integer('max_daily_hours_per_teacher')->default(8);
            $table->integer('max_daily_hours_per_class')->default(8);
            $table->boolean('enable_room_conflict')->default(true);
            $table->boolean('enable_teacher_conflict')->default(true);
            $table->boolean('enable_group_conflict')->default(true);
            $table->json('sms_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'max_weekly_hours_per_teacher',
                'max_consecutive_hours',
                'max_daily_hours_per_teacher',
                'max_daily_hours_per_class',
                'enable_room_conflict',
                'enable_teacher_conflict',
                'enable_group_conflict',
                'sms_config',
            ]);
        });
    }
};
