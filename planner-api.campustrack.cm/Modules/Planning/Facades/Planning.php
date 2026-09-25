<?php

namespace Modules\Planning\Facades;

use App\Models\CourseClass;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Modules\Planning\Entities\Planning as PlanningEntity;
use Modules\Planning\Entities\ShiftPlanning;

class Planning extends Facade
{
    // ///////////
    // Plannings//
    // ///////////

    /**
     * Return an empty Planning
     *
     * @return PlanningEntity
     */
    public static function newPlanning()
    {
        return new PlanningEntity;
    }

    /**
     * Get all plannings
     *
     * @param  bool  $pluck
     * @return mixed
     */
    public static function getAllPlannings($pluck = false)
    {
        $plannings = PlanningEntity::with(['shiftPlannings.room', 'shiftPlannings.course'])->latest();

        if ($pluck) {
            return $plannings->pluck('starting_date', 'id');
        }

        return $plannings->get();
    }

    /**
     * Create a new planning
     *
     * @return mixed
     */
    public static function createPlanning(object $data)
    {
        $planning = PlanningEntity::create($data->all());
        if ($data->input('shift_plannings')) {
            echo `<script>console.log(alert(`.($data->input('shift_plannings')).`));</script>`;
            foreach (explode(',', $data->input('shift_plannings')) as $shift_planning) {
                $shiftPlanning = ShiftPlanning::findOrFail($shift_planning);
                $newShiftPlanning = $shiftPlanning->replicate();
                $newShiftPlanning->planning_id = $planning->id;
                $newShiftPlanning->save();
            }
        }

        return $planning;
    }

    /**
     * Find a planning by ID
     *
     * @param  int  $id
     * @return mixed
     */
    public static function findPlanningById($id)
    {
        return PlanningEntity::with('shiftPlannings')->findOrFail($id);
    }

    /**
     * Update an planning
     *
     * @return mixed
     */
    public static function updatePlanning(int $id, object $data)
    {
        $planning = PlanningEntity::findOrFail($id);

        return $planning->update($data->all());
    }

    /**
     * Delete an planning
     *
     * @param  int  $id
     * @return mixed
     */
    public static function deletePlanning($id)
    {
        $planning = PlanningEntity::findOrFail($id);

        return $planning->delete();
    }

    // /////////
    // Classes//
    // /////////

    /**
     * Return an empty CourseClass
     *
     * @return CourseClass
     */
    public static function newclasse()
    {
        return new CourseClass;
    }

    /**
     * Get all work stations
     *
     * @param  bool  $pluck
     * @return mixed
     */
    public static function getAllClasses($pluck = false)
    {
        $classes = CourseClass::latest()->paginate(10);

        if ($pluck) {
            return $classes->pluck('name', 'id');
        }

        return $classes;
    }

    /**
     * Create a new work station
     *
     * @return mixed
     */
    public static function createClasse(object $data)
    {
        return CourseClass::create($data->all());
    }

    /**
     * Find a work station by ID
     *
     * @param  int  $id
     * @return mixed
     */
    public static function findClasseById($id)
    {
        return CourseClass::findOrFail($id);
    }

    /**
     * Update a work station
     *
     * @return mixed
     */
    public static function updateClasse(int $id, object $data)
    {
        $classe = CourseClass::findOrFail($id);

        return $classe->update($data->all());
    }

    /**
     * Delete a work station
     *
     * @return mixed
     */
    public static function deleteClasse($id)
    {
        $classe = CourseClass::findOrFail($id);

        return $classe->delete();
    }

    // ////////////////
    // Shift Planning//
    // ////////////////

    /**
     * Return an empty ShiftPlanning
     *
     * @return ShiftPlanning
     */
    public static function newShiftPlanning()
    {
        return new ShiftPlanning;
    }

    /**
     * Get all shift planning
     *
     *@param PlanningEntity
     *@param CourseClass
     * @return mixed
     */
    public static function getAllShiftPlannings(?PlanningEntity $planning = null, ?CourseClass $classe = null)
    {
        $shiftPlannings = ShiftPlanning::orderBy('date', 'desc')->orderBy('planning_id', 'asc');
        if ($classe?->id !== null) {
            $shiftPlannings = $shiftPlannings->where('course_class_id', $classe->id);
        }
        if ($planning?->id !== null) {
            $shiftPlannings = $shiftPlannings->where('planning_id', $planning->id);
        }

        return $shiftPlannings->paginate(10);
    }

    /**
     * Create a new shift planning
     *
     * @return mixed
     */
    public static function createShiftPlanning(array $data)
    {

        Log::debug('calculated hours: '.$data['calculated_hours']);

        $calculatedHours = $data['calculated_hours'] ?? 1;

        if ($calculatedHours <= 1) {
            return ShiftPlanning::create($data);
        }

        $startHour = $data['starting_hour'];
        $endHour = $data['ending_hour'];
        $notes = $data['notes'] ?? '';

        $startParts = explode(':', $startHour);
        $startHourInt = intval($startParts[0]);

        $parentShift = null;

        for ($i = 0; $i < $calculatedHours; $i++) {
            $currentStartHour = str_pad($startHourInt + $i, 2, '0', STR_PAD_LEFT).':00';
            $currentEndHour = str_pad($startHourInt + $i + 1, 2, '0', STR_PAD_LEFT).':00';

            $data['starting_hour'] = $currentStartHour;
            $data['ending_hour'] = $currentEndHour;
            $data['notes'] = $notes.' - Partie '.($i + 1).'/'.$calculatedHours;

            $shift = ShiftPlanning::create($data);

            if ($i === 0) {
                $parentShift = $shift;
            } else {
                $shift->update(['parent_id' => $parentShift->id]);
            }
        }

        return $parentShift;
    }

    /**
     * Find a shift planning by ID
     *
     * @param  int  $id
     * @return mixed
     */
    public static function findShiftPlanningById($id)
    {
        return ShiftPlanning::findOrFail($id);
    }

    /**
     * Update a shift planning
     *
     * @return mixed
     */
    public static function updateShiftPlanning(int $id, object $data)
    {
        $shiftPlanning = ShiftPlanning::findOrFail($id);
        $shiftPlanning->update($data->all());
        // $shiftPlanning->enseignants()->sync($data->input('enseignants'));
        $shiftPlanning->save();

        return $shiftPlanning;
    }

    /**
     * Delete a shift planning
     *
     * @param  int  $id
     * @return mixed
     */
    public static function deleteShiftPlanning($id)
    {
        $shiftPlanning = ShiftPlanning::findOrFail($id);

        $isParent = ShiftPlanning::where('parent_id', $shiftPlanning->id)->exists();
        $hasParent = $shiftPlanning->parent_id !== null;

        if ($isParent) {
            $children = ShiftPlanning::where('parent_id', $shiftPlanning->id)
                ->orderBy('starting_hour')
                ->get();

            $totalChildren = $children->count();
            $shiftPlanning->delete();

            $children->each(function ($child, $index) use ($totalChildren) {
                $newNotes = preg_replace('/ - Partie \d+\/\d+$/', '', $child->notes);
                $child->update([
                    'parent_id' => null,
                    'notes' => $newNotes.' - Partie '.($index + 1).'/'.$totalChildren,
                ]);
            });
        } elseif ($hasParent) {
            $parent = ShiftPlanning::find($shiftPlanning->parent_id);
            $siblings = ShiftPlanning::where('parent_id', $parent->id)
                ->where('id', '!=', $shiftPlanning->id)
                ->orderBy('starting_hour')
                ->get();

            $totalSiblings = $siblings->count();
            $deletedPosition = self::extractPartNumber($shiftPlanning->notes);

            $shiftPlanning->delete();

            $siblings->each(function ($sibling) use ($totalSiblings, $deletedPosition) {
                $currentPosition = self::extractPartNumber($sibling->notes);
                $newPosition = $currentPosition > $deletedPosition ? $currentPosition - 1 : $currentPosition;

                $newNotes = preg_replace('/ - Partie \d+\/\d+$/', '', $sibling->notes);
                $sibling->update([
                    'notes' => $newNotes.' - Partie '.$newPosition.'/'.$totalSiblings,
                ]);
            });

            if ($parent) {
                $remainingChildren = ShiftPlanning::where('parent_id', $parent->id)->get();
                if ($remainingChildren->isEmpty()) {
                    $parent->delete();
                }
            }
        } else {
            $shiftPlanning->delete();
        }

        return true;
    }

    private static function extractPartNumber(?string $notes): int
    {
        if (! $notes) {
            return 1;
        }

        if (preg_match('/ - Partie (\d+)\/(\d+)$/', $notes, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }
}
