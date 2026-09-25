<?php

namespace Modules\Planning\Http\Controllers;

use App\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Planning\Entities\ShiftPlanning;
use Modules\Planning\Facades\Planning;

class DashboardController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index()
    {
        $planning = Planning::getAllPlannings()->first();
        $shiftPlannings = $planning?->shiftPlannings;
        $now = now();
        $shiftPlannings = $shiftPlannings?->map(function ($shiftPlanning) use ($now) {
            if ($shiftPlanning->status === 'canceled') {
                return $shiftPlanning;
            }
            if ($now->lt($shiftPlanning->date)) {
                $shiftPlanning->status = 'pending';
            } elseif ($now->eq($shiftPlanning->date) && $now->lt($shiftPlanning->starting_hour)) {
                $shiftPlanning->status = 'pending';
            } elseif ($now->gte($shiftPlanning->starting_hour) && $now->lte($shiftPlanning->ending_hour) && $now->eq($shiftPlanning->date)) {
                $shiftPlanning->status = 'ongoing';
            } elseif ($now->gt($shiftPlanning->date)) {
                $shiftPlanning->status = 'completed';
            } elseif ($now->eq($shiftPlanning->date) && $now->gte($shiftPlanning->ending_hour)) {
                $shiftPlanning->status = 'completed';
            }
            $shiftPlanning->save();

            return $shiftPlanning;
        });
        $shiftPlanningTotal = $shiftPlannings?->where('date', '>=', Carbon::now()->startOfWeek())->count();
        $shiftPlanningWithoutEmployee = ShiftPlanning::doesntHave('employees')->where('planning_id', $planning?->id)->count();
        $shiftPlanningOngoing = $shiftPlannings?->where('status', 'ongoing')->count();
        $shiftPlanningCompleted = $shiftPlannings?->where('status', 'completed')->count();
        $shiftPlanningCanceled = $shiftPlannings?->where('status', 'canceled')->count();

        return $this->success([
            'shiftPlanningTotal' => $shiftPlanningTotal,
            'shiftPlanningCanceled' => $shiftPlanningCanceled,
            'shiftPlanningCompleted' => $shiftPlanningCompleted,
            'shiftPlanningOngoing' => $shiftPlanningOngoing,
            'shiftPlanningWithoutEmployee' => $shiftPlanningWithoutEmployee,
        ], 'Données du dashboard récupérées', 200);
    }

    //     public function shiftPlanning(Request $request)
    //     {
    //
    //         $shiftPlannings = ShiftPlanning::whereBetween('date', [$request->input('start_date'), $request->input('end_date')])->get();
    //         $now = now();
    //         $shiftPlannings = $shiftPlannings->map(function ($shiftPlanning) use ($now) {
    //             if ($shiftPlanning->status === 'canceled') {
    //                 return $shiftPlanning;
    //             }
    //             if ($now->lt($shiftPlanning->starting_hour)) {
    //                 $shiftPlanning->status = 'pending';
    //             } elseif ($now->gte($shiftPlanning->starting_hour) && $now->lte($shiftPlanning->ending_hour)) {
    //                 $shiftPlanning->status = 'ongoing';
    //             } elseif ($now->gt($shiftPlanning->ending_hour)) {
    //                 $shiftPlanning->status = 'completed';
    //             }
    //             $shiftPlanning->save();
    //             return $shiftPlanning;
    //         });
    //         $shiftPlanningTotal = $shiftPlannings->where('date', '>=', Carbon::now()->startOfWeek())->count();
    //         $shiftPlanningPending = $shiftPlannings->where('status', 'pending')->count();
    //         $shiftPlanningOngoing = $shiftPlannings->where('status', 'ongoing')->count();
    //         $shiftPlanningCompleted = $shiftPlannings->where('status', 'completed')->count();
    //         $shiftPlanningCanceled = $shiftPlannings->where('status', 'canceled')->count();
    //         return view('planning::components.modules.dashboard.shift_planning.shift_planning', compact('shiftPlanningTotal', 'shiftPlanningCanceled', 'shiftPlanningCompleted', 'shiftPlanningOngoing', 'shiftPlanningPending'));
    //     }
}
