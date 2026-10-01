<?php

namespace Modules\Planning\Http\Controllers;

use App\Traits\HttpResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Planning\Entities\Planning as PlanningEntity;
use Modules\Planning\Entities\ShiftPlanning;
use Modules\Planning\Facades\Planning;
use Modules\Planning\Http\Requests\StorePlanningRequest;
use Modules\Planning\Http\Requests\UpdatePlanningRequest;

class PlanningController extends Controller
{
    use AuthorizesRequests;
    use HttpResponses;

    public function index(Request $request)
    {
        $this->authorize('viewAny', PlanningEntity::class);

        $plannings = Planning::getAllPlannings();

        return $this->success(['plannings' => $plannings], 'Plannings récupérés');
    }

    public function store(StorePlanningRequest $request)
    {
        $this->authorize('create', PlanningEntity::class);

        $planning = Planning::createPlanning($request);

        return $this->success(
            ['planning' => $planning],
            'Planning créé avec succès',
            201
        );
    }

    public function show(Request $request, int $id)
    {
        $planning = Planning::findPlanningById($id);
        $this->authorize('view', $planning);

        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)
            ->with(['courseClass', 'teacher', 'course', 'room'])
            ->orderBy('date', 'asc')
            ->orderBy('starting_hour', 'asc')
            ->get();

        return $this->success([
            'planning' => $planning,
            // 'shift_plannings' => $shiftPlannings,
        ], 'Planning récupéré');
    }

    public function update(UpdatePlanningRequest $request, int $id)
    {
        $this->authorize('update', PlanningEntity::findOrFail($id));

        Planning::updatePlanning($id, $request);

        return $this->success(
            ['planning' => Planning::findPlanningById($id)],
            'Planning mis à jour avec succès',
            200
        );
    }

    public function destroy(int $id)
    {
        $this->authorize('delete', PlanningEntity::findOrFail($id));

        Planning::deletePlanning($id);

        return $this->success(null, 'Planning supprimé avec succès', 200);
    }
}
