<?php

namespace Modules\Planning\Services;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Room;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;

class SchedulingService
{
    protected ConflictDetectionService $conflictService;

    protected ResolutionProposalService $resolutionService;

    protected array $generatedPlannings = [];

    protected array $failedPlannings = [];

    protected array $warnings = [];

    public function __construct()
    {
        $this->conflictService = new ConflictDetectionService;
        $this->resolutionService = new ResolutionProposalService;
    }

    public function generateAutomatic(
        Planning $planning,
        array $courses,
        array $classes,
        array $options = []
    ): array {
        $this->generatedPlannings = [];
        $this->failedPlannings = [];
        $this->warnings = [];

        $startDate = Carbon::parse($planning->starting_date);
        $endDate = Carbon::parse($planning->ending_date);

        $dailyHours = $options['daily_hours'] ?? 6;
        $preferSameRoom = $options['prefer_same_room'] ?? true;
        $maxIterations = $options['max_iterations'] ?? 100;

        // Génération atomique : en cas d'échec imprévu, aucun shift n'est conservé
        DB::transaction(function () use ($classes, $courses, $startDate, $endDate, $dailyHours, $preferSameRoom, $maxIterations, $planning) {
            foreach ($classes as $classId) {
                $class = CourseClass::with('students')->find($classId);
                if (! $class) {
                    $this->warnings[] = "Classe ID $classId non trouvée";

                    continue;
                }

                foreach ($courses as $courseId) {
                    $course = Course::find($courseId);
                    if (! $course) {
                        $this->warnings[] = "Cours ID $courseId non trouvé";

                        continue;
                    }

                    $teachers = $course->teachers;
                    if ($teachers->isEmpty()) {
                        $this->warnings[] = "Aucun professeur assigné au cours: {$course->name}";

                        continue;
                    }

                    $hoursNeeded = $course->hours_per_week ?? 0;
                    if ($hoursNeeded <= 0) {
                        continue;
                    }

                    $this->scheduleCourseForClass(
                        $class,
                        $course,
                        $teachers,
                        $startDate,
                        $endDate,
                        $dailyHours,
                        $preferSameRoom,
                        $maxIterations,
                        $planning->id
                    );
                }
            }
        });

        return [
            'generated' => $this->generatedPlannings,
            'failed' => $this->failedPlannings,
            'warnings' => $this->warnings,
            'total_generated' => count($this->generatedPlannings),
            'total_failed' => count($this->failedPlannings),
        ];
    }

    protected function scheduleCourseForClass(
        CourseClass $class,
        Course $course,
        Collection $teachers,
        Carbon $startDate,
        Carbon $endDate,
        int $dailyHours,
        bool $preferSameRoom,
        int $maxIterations,
        int $planningId
    ): void {
        $hoursScheduled = 0;
        $hoursNeeded = $course->hours_per_week;
        $iteration = 0;

        $currentDate = $startDate->copy();
        $lastUsedRoom = null;

        while ($hoursScheduled < $hoursNeeded && $currentDate->lte($endDate) && $iteration < $maxIterations) {
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();

                continue;
            }

            $daySlots = $this->getAvailableDaySlots($currentDate, $dailyHours);

            foreach ($daySlots as $slot) {
                if ($hoursScheduled >= $hoursNeeded) {
                    break;
                }

                $slotDuration = $this->calculateSlotDuration($slot['start'], $slot['end']);
                if ($hoursScheduled + $slotDuration > $hoursNeeded) {
                    continue;
                }

                $room = $preferSameRoom && $lastUsedRoom
                    ? $this->findBestRoom($currentDate, $slot['start'], $slot['end'], $lastUsedRoom)
                    : $this->findBestRoom($currentDate, $slot['start'], $slot['end']);

                if (! $room) {
                    continue;
                }

                $teacher = $this->findAvailableTeacher($teachers, $currentDate, $slot['start'], $slot['end']);

                if (! $teacher) {
                    $availableTeachers = $this->conflictService->findAvailableTeachers(
                        $currentDate->format('Y-m-d'),
                        $slot['start'],
                        $slot['end']
                    );
                    if ($availableTeachers->isNotEmpty()) {
                        $teacher = $availableTeachers->first();
                    } else {
                        continue;
                    }
                }

                $shiftPlanning = ShiftPlanning::create([
                    'planning_id' => $planningId,
                    'course_class_id' => $class->id,
                    'course_id' => $course->id,
                    'teacher_id' => $teacher->id,
                    'room_id' => $room->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'starting_hour' => $slot['start'],
                    'ending_hour' => $slot['end'],
                    'number_teachers' => 1,
                    'status' => 'pending',
                ]);

                $this->generatedPlannings[] = $shiftPlanning;
                $hoursScheduled += $slotDuration;
                $lastUsedRoom = $room->id;

                if ($hoursScheduled >= $hoursNeeded) {
                    break;
                }
            }

            $currentDate->addDay();
            $iteration++;
        }

        if ($hoursScheduled < $hoursNeeded) {
            $this->failedPlannings[] = [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'hours_scheduled' => $hoursScheduled,
                'hours_needed' => $hoursNeeded,
                'reason' => 'Impossible de trouver des créneaux disponibles',
            ];
        }
    }

    protected function getAvailableDaySlots(Carbon $date, int $maxHours): array
    {
        $slots = [
            ['start' => '08:00', 'end' => '09:00'],
            ['start' => '09:00', 'end' => '10:00'],
            ['start' => '10:00', 'end' => '11:00'],
            ['start' => '11:00', 'end' => '12:00'],
            ['start' => '13:00', 'end' => '14:00'],
            ['start' => '14:00', 'end' => '15:00'],
            ['start' => '15:00', 'end' => '16:00'],
            ['start' => '16:00', 'end' => '17:00'],
        ];

        return array_slice($slots, 0, $maxHours);
    }

    protected function findBestRoom(Carbon $date, string $startTime, string $endTime, ?int $preferredRoomId = null): ?Room
    {
        $availableRooms = $this->conflictService->findAvailableRooms(
            $date->format('Y-m-d'),
            $startTime,
            $endTime
        );

        if ($availableRooms->isEmpty()) {
            return null;
        }

        if ($preferredRoomId) {
            $preferredRoom = $availableRooms->firstWhere('id', $preferredRoomId);
            if ($preferredRoom) {
                return $preferredRoom;
            }
        }

        return $availableRooms->first();
    }

    protected function findAvailableTeacher(Collection $teachers, Carbon $date, string $startTime, string $endTime): ?Teacher
    {
        $dateStr = $date->format('Y-m-d');

        foreach ($teachers as $teacher) {
            $isAvailable = ! $this->conflictService->findAvailableTeachers($dateStr, $startTime, $endTime)
                ->contains('id', $teacher->id);

            if ($isAvailable) {
                return $teacher;
            }
        }

        return null;
    }

    protected function calculateSlotDuration($start, $end): float
    {
        $startTime = $start instanceof Carbon ? $start->format('H:i:s') : $start;
        $endTime = $end instanceof Carbon ? $end->format('H:i:s') : $end;

        $startTimestamp = strtotime($startTime);
        $endTimestamp = strtotime($endTime);

        return round(($endTimestamp - $startTimestamp) / 3600, 2);
    }

    public function createManual(ShiftPlanning $shiftPlanning): array
    {
        $conflicts = $this->conflictService->detectConflicts($shiftPlanning);

        if (! empty($conflicts)) {
            $proposals = $this->resolutionService->getProposalsForConflicts($conflicts, $shiftPlanning);

            return [
                'success' => false,
                'conflicts' => $conflicts,
                'proposals' => $proposals,
                'message' => 'Conflits détectés. Propositions de résolution disponibles.',
            ];
        }

        $shiftPlanning->save();

        return [
            'success' => true,
            'shift_planning' => $shiftPlanning,
            'message' => 'Cours planifié avec succès',
        ];
    }

    public function duplicateShiftPlanning(ShiftPlanning $shiftPlanning, array $targetDates): array
    {
        $duplicated = [];

        foreach ($targetDates as $date) {
            $newShift = $shiftPlanning->replicate();
            $newShift->date = $date;
            $newShift->status = 'pending';
            $newShift->save();

            $conflicts = $this->conflictService->detectConflicts($newShift, $newShift->id);

            $duplicated[] = [
                'original_id' => $shiftPlanning->id,
                'new_id' => $newShift->id,
                'date' => $date,
                'has_conflicts' => ! empty($conflicts),
                'conflicts' => $conflicts,
            ];
        }

        return $duplicated;
    }

    public function getStatistics(Planning $planning): array
    {
        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)->get();

        $totalHours = $shiftPlannings->sum(function ($sp) {
            return $this->calculateSlotDuration($sp->starting_hour, $sp->ending_hour);
        });

        $teacherHours = $shiftPlannings->groupBy('teacher_id')->map(function ($group) {
            return $group->sum(function ($sp) {
                return $this->calculateSlotDuration($sp->starting_hour, $sp->ending_hour);
            });
        });

        $roomUsage = $shiftPlannings->groupBy('room_id')->map(function ($group) {
            return $group->count();
        });

        $classHours = $shiftPlannings->groupBy('course_class_id')->map(function ($group) {
            return $group->sum(function ($sp) {
                return $this->calculateSlotDuration($sp->starting_hour, $sp->ending_hour);
            });
        });

        $conflicts = $this->conflictService->detectAllConflictsForPlanning($planning);

        return [
            'total_sessions' => $shiftPlannings->count(),
            'total_hours' => round($totalHours, 2),
            'by_teacher' => $teacherHours,
            'by_room' => $roomUsage,
            'by_class' => $classHours,
            'conflicts_count' => count($conflicts),
            'conflicts' => $conflicts,
        ];
    }

    public function optimizeSchedule(Planning $planning): array
    {
        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)
            ->orderBy('date')
            ->orderBy('starting_hour')
            ->get();

        $optimized = 0;
        $skipped = 0;

        foreach ($shiftPlannings as $shiftPlanning) {
            $conflicts = $this->conflictService->detectConflicts($shiftPlanning, $shiftPlanning->id);

            if (empty($conflicts)) {
                $skipped++;

                continue;
            }

            $resolved = $this->resolutionService->autoResolve($shiftPlanning);

            if ($resolved) {
                $optimized++;
            }
        }

        return [
            'optimized' => $optimized,
            'skipped' => $skipped,
            'message' => "$optimized conflits résolus automatiquement",
        ];
    }
}
