<?php

namespace Modules\Planning\Services;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;

class DoubleurService
{
    protected ConflictDetectionService $conflictService;

    public function __construct()
    {
        $this->conflictService = new ConflictDetectionService;
    }

    public function createDoubleur(
        ShiftPlanning $original,
        array $targetClassIds,
        array $options = []
    ): array {
        $originalDate = $original->date instanceof Carbon
            ? $original->date->format('Y-m-d')
            : $original->date;
        $originalStartTime = $original->starting_hour instanceof Carbon
            ? $original->starting_hour->format('H:i:s')
            : $original->starting_hour;
        $originalEndTime = $original->ending_hour instanceof Carbon
            ? $original->ending_hour->format('H:i:s')
            : $original->ending_hour;

        $sameRoom = $options['same_room'] ?? false;
        $sameTime = $options['same_time'] ?? true;
        $checkConflicts = $options['check_conflicts'] ?? true;

        $created = [];
        $failed = [];

        foreach ($targetClassIds as $classId) {
            if ($classId === $original->course_class_id) {
                continue;
            }

            $newShift = $original->replicate();
            $newShift->course_class_id = $classId;
            $newShift->parent_id = $original->id;
            $newShift->status = 'pending';

            if (! $sameRoom) {
                $availableRooms = $this->conflictService->findAvailableRooms(
                    $originalDate,
                    $originalStartTime,
                    $originalEndTime
                );

                if ($availableRooms->isNotEmpty()) {
                    $newShift->room_id = $availableRooms->first()->id;
                } else {
                    $failed[] = [
                        'class_id' => $classId,
                        'reason' => 'Aucune salle disponible',
                    ];

                    continue;
                }
            }

            if ($checkConflicts) {
                $conflicts = $this->conflictService->detectConflicts($newShift, $newShift->id);

                if (! empty($conflicts)) {
                    $failed[] = [
                        'class_id' => $classId,
                        'conflicts' => $conflicts,
                        'reason' => 'Conflits détectés',
                    ];

                    continue;
                }
            }

            $newShift->save();

            $created[] = [
                'id' => $newShift->id,
                'class_id' => $classId,
                'room_id' => $newShift->room_id,
            ];
        }

        return [
            'original_id' => $original->id,
            'created' => $created,
            'failed' => $failed,
            'total_created' => count($created),
            'total_failed' => count($failed),
        ];
    }

    public function findDoubleurOpportunities(Planning $planning, array $criteria = []): array
    {
        $minSameTimeSlot = $criteria['min_same_time_slot'] ?? 2;

        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)
            ->where('date', '>=', Carbon::now()->format('Y-m-d'))
            ->with(['course', 'room', 'teacher'])
            ->get()
            ->groupBy(function ($sp) {
                return $sp->date.' '.$sp->starting_hour.' '.$sp->ending_hour;
            });

        $opportunities = [];

        foreach ($shiftPlannings as $timeSlot => $plannings) {
            if ($plannings->count() < 2) {
                continue;
            }

            $byCourse = $plannings->groupBy('course_id');

            foreach ($byCourse as $courseId => $coursePlannings) {
                if ($coursePlannings->count() < $minSameTimeSlot) {
                    continue;
                }

                $classes = $coursePlannings->pluck('course_class_id')->toArray();

                $availableRooms = $this->conflictService->findAvailableRooms(
                    $coursePlannings->first()->date,
                    $coursePlannings->first()->starting_hour,
                    $coursePlannings->first()->ending_hour
                );

                if ($availableRooms->isNotEmpty()) {
                    $opportunities[] = [
                        'time_slot' => $timeSlot,
                        'course_id' => $courseId,
                        'course_name' => $coursePlannings->first()->course?->name,
                        'classes_involved' => $classes,
                        'suggested_room' => $availableRooms->first(),
                        'potential_savings' => ($coursePlannings->count() - 1) * $this->calculateDuration(
                            $coursePlannings->first()->starting_hour,
                            $coursePlannings->first()->ending_hour
                        ),
                    ];
                }
            }
        }

        return $opportunities;
    }

    public function detectSimultaneousCourses(Planning $planning): array
    {
        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)
            ->with(['courseClass', 'course', 'teacher', 'room'])
            ->get();

        $groups = $shiftPlannings->groupBy(function ($sp) {
            return $sp->date.'|'.$sp->starting_hour.'|'.$sp->ending_hour;
        });

        $simultaneous = [];

        foreach ($groups as $key => $plannings) {
            if ($plannings->count() > 1) {
                $parts = explode('|', $key);

                $simultaneous[] = [
                    'date' => $parts[0],
                    'starting_hour' => $parts[1],
                    'ending_hour' => $parts[2],
                    'courses' => $plannings->map(function ($sp) {
                        return [
                            'id' => $sp->id,
                            'class' => $sp->courseClass?->name,
                            'course' => $sp->course?->name,
                            'teacher' => $sp->teacher?->full_name,
                            'room' => $sp->room?->name,
                        ];
                    })->toArray(),
                    'total_classes' => $plannings->count(),
                ];
            }
        }

        return $simultaneous;
    }

    public function optimizeDoubleurs(Planning $planning): array
    {
        $opportunities = $this->findDoubleurOpportunities($planning);

        $optimized = [];
        $skipped = 0;

        foreach ($opportunities as $opportunity) {
            $classes = $opportunities['classes_involved'];
            if (count($classes) < 2) {
                $skipped++;

                continue;
            }

            $originalClass = array_shift($classes);
            $originalShift = ShiftPlanning::where('planning_id', $planning->id)
                ->where('course_class_id', $originalClass)
                ->where('course_id', $opportunity['course_id'])
                ->where('date', $opportunity['date'])
                ->first();

            if (! $originalShift) {
                $skipped++;

                continue;
            }

            $result = $this->createDoubleur(
                $originalShift,
                $classes,
                ['same_room' => true, 'same_time' => true, 'check_conflicts' => true]
            );

            $optimized[] = $result;
        }

        return [
            'optimized' => $optimized,
            'skipped' => $skipped,
            'total_optimizations' => count($optimized),
            'message' => count($optimized).' doubleurs créés',
        ];
    }

    public function getDoubleurGroups(Planning $planning): Collection
    {
        return ShiftPlanning::where('planning_id', $planning->id)
            ->whereNotNull('parent_id')
            ->orWhereHas('parent')
            ->with(['parent', 'courseClass', 'course'])
            ->get()
            ->groupBy('parent_id');
    }

    public function getPotentialDoubleurs(Planning $planning): array
    {
        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)
            ->with(['courseClass', 'course'])
            ->get();

        $byCourse = $shiftPlannings->groupBy('course_id');

        $potential = [];

        foreach ($byCourse as $courseId => $plannings) {
            $course = $plannings->first()->course;
            $classes = $plannings->pluck('course_class_id')->unique()->values();

            if ($classes->count() > 1 && $course) {
                $potential[] = [
                    'course_id' => $courseId,
                    'course_name' => $course->name,
                    'classes' => $classes->toArray(),
                    'total_sessions' => $plannings->count(),
                    'could_be_doubled' => true,
                ];
            }
        }

        return $potential;
    }

    protected function calculateDuration($start, $end): float
    {
        $startTime = $start instanceof Carbon ? $start->format('H:i:s') : $start;
        $endTime = $end instanceof Carbon ? $end->format('H:i:s') : $end;

        $startTimestamp = strtotime($startTime);
        $endTimestamp = strtotime($endTime);

        return round(($endTimestamp - $startTimestamp) / 3600, 2);
    }

    public function getRoomCapacityForDoubleur(Room $room, int $totalStudents): bool
    {
        return $room->capacity >= $totalStudents;
    }

    public function suggestRoomsForDoubleur(
        string $date,
        string $startTime,
        string $endTime,
        int $requiredCapacity
    ): Collection {
        return $this->conflictService->findAvailableRooms($date, $startTime, $endTime)
            ->filter(function ($room) use ($requiredCapacity) {
                return $room->capacity >= $requiredCapacity;
            })
            ->sortByDesc('capacity');
    }
}
