<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_plannings', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('description')
                ->constrained('departments')->nullOnDelete();
        });

        // Rétro-remplit department_id depuis le premier créneau de chaque planning.
        $plannings = DB::table('planning_plannings')->whereNull('department_id')->get();
        foreach ($plannings as $planning) {
            $departmentId = DB::table('planning_shift_plannings')
                ->join('classes', 'classes.id', '=', 'planning_shift_plannings.course_class_id')
                ->where('planning_shift_plannings.planning_id', $planning->id)
                ->value('classes.department_id');

            if ($departmentId) {
                DB::table('planning_plannings')
                    ->where('id', $planning->id)
                    ->update(['department_id' => $departmentId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('planning_plannings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
