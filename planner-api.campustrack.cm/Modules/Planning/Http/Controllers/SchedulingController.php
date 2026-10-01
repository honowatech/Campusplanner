<?php

namespace Modules\Planning\Http\Controllers;

use App\Traits\HttpResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;
use Modules\Planning\Services\ConflictDetectionService;
use Modules\Planning\Services\DoubleurService;
use Modules\Planning\Services\RecurrenceService;
use Modules\Planning\Services\ResolutionProposalService;
use Modules\Planning\Services\SchedulingService;

class SchedulingController extends Controller
{
    use AuthorizesRequests;
    use HttpResponses;

    protected SchedulingService $schedulingService;

    protected ConflictDetectionService $conflictService;

    protected ResolutionProposalService $resolutionService;

    protected RecurrenceService $recurrenceService;

    protected DoubleurService $doubleurService;

    public function __construct()
    {
        $this->schedulingService = new SchedulingService;
        $this->conflictService = new ConflictDetectionService;
        $this->resolutionService = new ResolutionProposalService;
        $this->recurrenceService = new RecurrenceService;
        $this->doubleurService = new DoubleurService;
    }

    public function generate(Request $request)
    {
        $this->authorize('generate', Planning::class);

        $request->validate([
            'planning_id' => 'required|exists:planning_plannings,id',
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
            'classes' => 'required|array',
            'classes.*' => 'exists:classes,id',
            'daily_hours' => 'nullable|integer|min:1|max:10',
            'prefer_same_room' => 'nullable|boolean',
            // Borne serveur : évite qu'un appel légitime ne boucle des heures
            'max_iterations' => 'nullable|integer|min:1|max:1000',
        ]);

        $planning = Planning::findOrFail($request->input('planning_id'));
        $courses = $request->input('courses');
        $classes = $request->input('classes');

        $options = [
            'daily_hours' => $request->input('daily_hours', 6),
            'prefer_same_room' => $request->input('prefer_same_room', true),
            'max_iterations' => $request->input('max_iterations', 100),
        ];

        $result = $this->schedulingService->generateAutomatic(
            $planning,
            $courses,
            $classes,
            $options
        );

        return $this->success($result, 'Génération terminée', 201);
    }

    public function detectConflicts(Request $request)
    {
        $this->authorize('detectConflicts', Planning::class);

        $request->validate([
            'planning_id' => 'required|exists:planning_plannings,id',
        ]);

        $planning = Planning::findOrFail($request->input('planning_id'));
        $conflicts = $this->conflictService->detectAllConflictsForPlanning($planning);

        return $this->success([
            'conflicts' => $conflicts,
            'total_conflicts' => count($conflicts),
        ], 'Détection de conflits terminée');
    }

    public function resolveConflicts(Request $request)
    {
        $this->authorize('resolveConflicts', Planning::class);

        $request->validate([
            'shift_planning_id' => 'required|exists:planning_shift_plannings,id',
            'auto_resolve' => 'nullable|boolean',
        ]);

        $shiftPlanning = ShiftPlanning::findOrFail($request->input('shift_planning_id'));

        if ($request->input('auto_resolve', true)) {
            $result = $this->resolutionService->autoResolve($shiftPlanning);

            if ($result) {
                return $this->success([
                    'shift_planning' => $result,
                    'resolved' => true,
                ], 'Conflit résolu automatiquement');
            }

            return $this->error(null, 'Impossible de résoudre automatiquement', 422);
        }

        $conflicts = $this->conflictService->detectConflicts($shiftPlanning, $shiftPlanning->id);
        $proposals = $this->resolutionService->getProposalsForConflicts($conflicts, $shiftPlanning);

        return $this->success([
            'conflicts' => $conflicts,
            'proposals' => $proposals,
        ], 'Propositions de résolution');
    }

    public function statistics(Request $request, int $planningId)
    {
        $planning = Planning::findOrFail($planningId);
        $this->authorize('view', $planning);
        $stats = $this->schedulingService->getStatistics($planning);

        return $this->success($stats, 'Statistiques récupérées');
    }

    public function optimize(Request $request, int $planningId)
    {
        $this->authorize('optimize', Planning::class);

        $planning = Planning::findOrFail($planningId);
        $result = $this->schedulingService->optimizeSchedule($planning);

        return $this->success($result, 'Optimisation terminée');
    }

    public function makeRecurring(Request $request, int $id)
    {
        $this->authorize('update', ShiftPlanning::findOrFail($id));

        $request->validate([
            'pattern' => 'required|array',
            'end_date' => 'required|date',
        ]);

        $shiftPlanning = ShiftPlanning::findOrFail($id);

        $errors = $this->recurrenceService->validateRecurrencePattern($request->input('pattern'));
        if (! empty($errors)) {
            return $this->error(['errors' => $errors], implode(', ', $errors), 422);
        }

        $result = $this->recurrenceService->createRecurring($shiftPlanning, [
            'pattern' => $request->input('pattern'),
            'end_date' => $request->input('end_date'),
        ]);

        return $this->success($result, 'Série récurrente créée', 201);
    }

    public function updateRecurringSeries(Request $request, int $id)
    {
        $this->authorize('update', ShiftPlanning::findOrFail($id));

        $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'starting_hour' => 'nullable|date_format:H:i',
            'ending_hour' => 'nullable|date_format:H:i',
        ]);

        $parent = ShiftPlanning::findOrFail($id);

        $data = $request->only([
            'room_id',
            'teacher_id',
            'starting_hour',
            'ending_hour',
            'notes',
        ]);

        $result = $this->recurrenceService->updateRecurringSeries($parent, $data);

        return $this->success($result, 'Série mise à jour');
    }

    public function deleteRecurringSeries(Request $request, int $id)
    {
        $this->authorize('delete', ShiftPlanning::findOrFail($id));

        $request->validate([
            'delete_children' => 'nullable|boolean',
        ]);

        $parent = ShiftPlanning::findOrFail($id);
        $result = $this->recurrenceService->deleteRecurringSeries(
            $parent,
            $request->input('delete_children', true)
        );

        return $this->success($result, 'Série supprimée');
    }

    public function expandRecurring(int $id)
    {
        $parent = ShiftPlanning::findOrFail($id);
        $this->authorize('view', $parent);
        $shifts = $this->recurrenceService->expandRecurringSeries($parent);

        return $this->success([
            'shifts' => $shifts,
            'total' => $shifts->count(),
        ], 'Série développée');
    }

    public function createDoubleur(Request $request, int $id)
    {
        $this->authorize('update', ShiftPlanning::findOrFail($id));

        $request->validate([
            'target_class_ids' => 'required|array',
            'target_class_ids.*' => 'exists:classes,id',
            'same_room' => 'nullable|boolean',
            'same_time' => 'nullable|boolean',
            'check_conflicts' => 'nullable|boolean',
        ]);

        $shiftPlanning = ShiftPlanning::findOrFail($id);

        $result = $this->doubleurService->createDoubleur(
            $shiftPlanning,
            $request->input('target_class_ids'),
            [
                'same_room' => $request->input('same_room', false),
                'same_time' => $request->input('same_time', true),
                'check_conflicts' => $request->input('check_conflicts', true),
            ]
        );

        return $this->success($result, 'Doubleur créé', 201);
    }

    public function doubleurOpportunities(Request $request)
    {
        $this->authorize('viewAny', Planning::class);

        $request->validate([
            'planning_id' => 'required|exists:planning_plannings,id',
            'min_same_time_slot' => 'nullable|integer|min:2',
        ]);

        $planning = Planning::findOrFail($request->input('planning_id'));

        $opportunities = $this->doubleurService->findDoubleurOpportunities($planning, [
            'min_same_time_slot' => $request->input('min_same_time_slot', 2),
        ]);

        return $this->success([
            'opportunities' => $opportunities,
            'total' => count($opportunities),
        ], 'Opportunités de doubleur récupérées');
    }

    public function simultaneousCourses(Request $request)
    {
        $this->authorize('viewAny', Planning::class);

        $request->validate([
            'planning_id' => 'required|exists:planning_plannings,id',
        ]);

        $planning = Planning::findOrFail($request->input('planning_id'));
        $simultaneous = $this->doubleurService->detectSimultaneousCourses($planning);

        return $this->success([
            'simultaneous' => $simultaneous,
            'total' => count($simultaneous),
        ], 'Cours simultanés récupérés');
    }

    public function getProposals(int $id)
    {
        $shiftPlanning = ShiftPlanning::findOrFail($id);
        $this->authorize('view', $shiftPlanning);
        $conflicts = $this->conflictService->detectConflicts($shiftPlanning, $shiftPlanning->id);

        if (empty($conflicts)) {
            return $this->success([
                'conflicts' => [],
                'proposals' => [],
            ], 'Aucun conflit détecté');
        }

        $proposals = $this->resolutionService->getProposalsForConflicts($conflicts, $shiftPlanning);

        return $this->success([
            'conflicts' => $conflicts,
            'proposals' => $proposals,
        ], 'Propositions récupérées');
    }

    public function applyProposal(Request $request, int $id)
    {
        $this->authorize('update', ShiftPlanning::findOrFail($id));

        $validated = $request->validate([
            'proposal' => 'required|array',
            'proposal.type' => 'required|in:change_room,change_teacher,change_time',
            'proposal.room_id' => 'nullable|integer|exists:rooms,id',
            'proposal.teacher_id' => 'nullable|integer|exists:teachers,id',
            'proposal.starting_hour' => 'nullable|string',
            'proposal.ending_hour' => 'nullable|string',
        ]);

        $shiftPlanning = ShiftPlanning::findOrFail($id);
        $proposal = $validated['proposal'];

        $updates = [];

        switch ($proposal['type']) {
            case 'change_room':
                $updates['room_id'] = $proposal['room_id'];
                break;
            case 'change_teacher':
                $updates['teacher_id'] = $proposal['teacher_id'];
                break;
            case 'change_time':
                $updates['starting_hour'] = $proposal['starting_hour'];
                $updates['ending_hour'] = $proposal['ending_hour'];
                break;
        }

        $shiftPlanning->update($updates);

        $conflicts = $this->conflictService->detectConflicts($shiftPlanning, $shiftPlanning->id);

        return $this->success([
            'shift_planning' => $shiftPlanning->fresh(),
            'remaining_conflicts' => $conflicts,
        ], 'Proposition appliquée');
    }
}
