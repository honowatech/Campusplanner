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

        $user = $request->user();

        $query = PlanningEntity::query()
            ->with(['shiftPlannings.room', 'shiftPlannings.course'])
            ->latest();

        // Périmètre de consultation : global > département > matière > classe.
        if (! $user->hasPermissionTo('plannings.view.all')) {
            if ($user->hasPermissionTo('plannings.view.department')) {
                $query->where('department_id', $user->department_id);
            } elseif ($user->hasPermissionTo('plannings.view.subject')) {
                $query->whereHas('shiftPlannings', function ($q) use ($user) {
                    $q->whereIn('course_id', $user->courseIds());
                });
            } elseif ($user->hasPermissionTo('plannings.view.class')) {
                $query->whereHas('shiftPlannings', function ($q) use ($user) {
                    $q->whereIn('course_class_id', $user->classIds());
                });
            }
        }

        $plannings = $query->get();

        return $this->success(['plannings' => $plannings], 'Plannings récupérés');
    }

    public function store(StorePlanningRequest $request)
    {
        $this->authorize('create', PlanningEntity::class);

        $data = $request->validated();

        // Borne le département au périmètre de l'utilisateur (responsable).
        $user = $request->user();
        if (! $user->hasRole('super-admin') && ! $user->hasPermissionTo('plannings.edit.all')) {
            $data['department_id'] = $user->department_id;
        }

        $planning = PlanningEntity::create($data);

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

        // Les shifts sont renvoyés avec leurs relations pour que les rôles en
        // lecture seule (professeur, étudiant) puissent afficher l'emploi du
        // temps sans charger séparément teachers/rooms/classes (403 pour eux).
        $planning->setRelation('shiftPlannings', $shiftPlannings);

        return $this->success([
            'planning' => $planning,
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
