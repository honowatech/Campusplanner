<?php

namespace Modules\Planning\Http\Controllers;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Room;
use App\Models\Teacher;
use App\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Planning\Entities\ShiftPlanning;
use Modules\Planning\Facades\Planning;
use Modules\Planning\Http\Requests\StoreShiftPlanningRequest;
use Modules\Planning\Http\Requests\UpdateShiftPlanningRequest;

class ShiftPlanningController extends Controller
{
    use AuthorizesRequests;
    use HttpResponses;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ShiftPlanning::class);

        $plannings = Planning::getAllPlannings();
        $classes = CourseClass::orderBy('name')->pluck('name', 'id');

        $shiftPlannings = ShiftPlanning::with(['courseClass', 'teacher', 'course', 'room', 'planning'])
            ->orderBy('date', 'desc')
            ->orderBy('starting_hour', 'desc')
            ->paginate(9);

        $this->updateStatuses($shiftPlannings);

        if ($request->expectsJson() || $request->input('json')) {
            return $this->success([
                'shift_plannings' => $shiftPlannings,
                'plannings' => $plannings,
                'classes' => $classes,
            ], 'Cours planifiés récupérés');
        }

        if ($request->input('planning')) {
            $shiftPlannings = ShiftPlanning::where('planning_id', $request->input('planning'))
                ->with(['courseClass', 'teacher', 'course', 'room'])
                ->orderBy('date', 'asc')
                ->orderBy('starting_hour', 'asc')
                ->get();

            return view('planning::components.modules.shift_planning.shift_plannings_list', compact('shiftPlannings'));
        }

        $planning = $plannings->first();

        return view('planning::shift_planning.index', compact('shiftPlannings', 'plannings', 'classes', 'planning'));
    }

    public function store(StoreShiftPlanningRequest $request)
    {
        $this->authorize('create', ShiftPlanning::class);

        // Log::debug($request->validated());

        $data = $request->validated();

        $startingHour = $data['starting_hour'];
        $endingHour = $data['ending_hour'];

        // Durée réelle en heures (arrondie au supérieur, minimum 1).
        // Ne pas faire d'arithmétique décimale sur HHMM : « 09:45 » - « 08:30 »
        // n'est pas 1,15 h mais 1,25 h.
        $calculatedHours = 1;
        if ($startingHour && $endingHour) {
            $durationSeconds = strtotime($endingHour) - strtotime($startingHour);
            $calculatedHours = max(1, (int) ceil($durationSeconds / 3600));
        }

        $data['calculated_hours'] = $calculatedHours;

        $shiftPlanning = Planning::createShiftPlanning($data);

        return $this->success(
            ['shift_planning' => $shiftPlanning],
            'Cours planifié créé avec succès',
            201
        );
    }

    public function show(Request $request, int $id)
    {
        $shiftPlanning = ShiftPlanning::with(['courseClass', 'teacher', 'course', 'room', 'planning'])->findOrFail($id);
        $this->authorize('view', $shiftPlanning);

        return $this->success(['shift_planning' => $shiftPlanning], 'Cours planifié récupéré');
    }

    public function update(UpdateShiftPlanningRequest $request, int $id)
    {
        $this->authorize('update', ShiftPlanning::findOrFail($id));

        $shiftPlanning = Planning::updateShiftPlanning($id, $request);

        return $this->success(
            ['shift_planning' => Planning::findShiftPlanningById($id)],
            'Cours planifié mis à jour avec succès',
            200
        );
    }

    public function destroy(int $id)
    {
        $this->authorize('delete', ShiftPlanning::findOrFail($id));

        Planning::deleteShiftPlanning($id);

        return $this->success(null, 'Cours planifié supprimé avec succès', 200);
    }

    public function getForm(Request $request)
    {
        $this->authorize('viewAny', ShiftPlanning::class);

        $shiftPlanning = $request->input('id')
            ? Planning::findShiftPlanningById($request->input('id'))
            : Planning::newShiftPlanning();

        $plannings = Planning::getAllPlannings();
        $classes = CourseClass::orderBy('name')->pluck('name', 'id');
        $courses = Course::orderBy('name')->pluck('name', 'id');
        $teachers = Teacher::orderBy('first_name')->get();

        return view('planning::components.modules.shift_planning.shift_plannings_form',
            compact('shiftPlanning', 'plannings', 'classes', 'teachers', 'courses'));
    }

    public function getList(Request $request)
    {
        $this->authorize('viewAny', ShiftPlanning::class);

        $planning = $request->input('planning_id')
            ? Planning::findPlanningById($request->input('planning_id'))
            : Planning::newPlanning();

        $classe = $request->input('course_class_id')
            ? CourseClass::findOrFail($request->input('course_class_id'))
            : null;

        $shiftPlannings = Planning::getAllShiftPlannings($planning, $classe);

        return view('planning::components.modules.shift_planning.shift_plannings_list', compact('shiftPlannings'));
    }

    public function teachersSelect(Request $request)
    {
        $this->authorize('viewAny', ShiftPlanning::class);

        $search = $request->input('search', '');
        $teachers = Teacher::where('first_name', 'LIKE', '%'.$search.'%')
            ->orWhere('last_name', 'LIKE', '%'.$search.'%')
            ->orWhere('email', 'LIKE', '%'.$search.'%')
            ->limit(10)
            ->get();

        return $this->success(['teachers' => $teachers], 'Enseignants récupérés', 200);
    }

    public function roomsSelect(Request $request)
    {
        $this->authorize('viewAny', ShiftPlanning::class);

        $search = $request->input('search', '');
        $rooms = Room::where('name', 'LIKE', '%'.$search.'%')
            ->orWhere('code', 'LIKE', '%'.$search.'%')
            ->active()
            ->limit(10)
            ->get();

        return $this->success(['rooms' => $rooms], 'Salles récupérées', 200);
    }

    protected function updateStatuses($shiftPlannings): void
    {
        $now = Carbon::now();

        foreach ($shiftPlannings as $shiftPlanning) {
            if ($shiftPlanning->status === 'canceled') {
                continue;
            }

            $shiftDate = $shiftPlanning->date instanceof Carbon
                ? $shiftPlanning->date
                : Carbon::parse($shiftPlanning->date);

            $startHour = $shiftPlanning->starting_hour instanceof Carbon
                ? $shiftPlanning->starting_hour
                : Carbon::parse($shiftPlanning->starting_hour);

            $endHour = $shiftPlanning->ending_hour instanceof Carbon
                ? $shiftPlanning->ending_hour
                : Carbon::parse($shiftPlanning->ending_hour);

            $newStatus = $shiftPlanning->status;

            if ($now->lt($shiftDate)) {
                $newStatus = 'pending';
            } elseif ($now->eq($shiftDate) && $now->lt($startHour)) {
                $newStatus = 'pending';
            } elseif ($now->gte($startHour) && $now->lte($endHour) && $now->eq($shiftDate)) {
                $newStatus = 'ongoing';
            } elseif ($now->gt($shiftDate)) {
                $newStatus = 'completed';
            } elseif ($now->eq($shiftDate) && $now->gte($endHour)) {
                $newStatus = 'completed';
            }

            if ($newStatus !== $shiftPlanning->status) {
                $shiftPlanning->status = $newStatus;
                $shiftPlanning->save();
            }
        }
    }
}
