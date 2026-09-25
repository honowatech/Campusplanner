<?php

namespace Modules\Planning\Services;

use App\Models\CourseClass;
use App\Models\Teacher;
use Carbon\Carbon;
use Modules\Planning\Entities\ShiftPlanning;

class ResolutionProposalService
{
    protected ConflictDetectionService $conflictService;

    protected array $proposals = [];

    public function __construct()
    {
        $this->conflictService = new ConflictDetectionService;
    }

    public function getProposalsForConflicts(array $conflicts, ShiftPlanning $shiftPlanning): array
    {
        $this->proposals = [];

        foreach ($conflicts as $conflict) {
            switch ($conflict['type']) {
                case ConflictDetectionService::TYPE_ROOM_CONFLICT:
                    $this->proposeAlternativeRooms($shiftPlanning, $conflict);
                    break;
                case ConflictDetectionService::TYPE_TEACHER_CONFLICT:
                    $this->proposeAlternativeTeachers($shiftPlanning, $conflict);
                    break;
                case ConflictDetectionService::TYPE_CLASS_CONFLICT:
                    $this->proposeAlternativeTimeSlots($shiftPlanning, $conflict);
                    break;
                case ConflictDetectionService::TYPE_ROOM_BLOCKING:
                    $this->proposeAlternativeRooms($shiftPlanning, $conflict);
                    break;
                case ConflictDetectionService::TYPE_TEACHER_BLOCKING:
                    $this->proposeAlternativeTeachers($shiftPlanning, $conflict);
                    break;
                case ConflictDetectionService::TYPE_HOURS_EXCEEDED:
                    $this->proposeAlternativeTeachers($shiftPlanning, $conflict);
                    break;
            }
        }

        return $this->proposals;
    }

    public function proposeAlternativeRooms(ShiftPlanning $shiftPlanning, array $conflict): array
    {
        $proposals = [];
        $date = $shiftPlanning->date instanceof Carbon
            ? $shiftPlanning->date->format('Y-m-d')
            : $shiftPlanning->date;
        $startTime = $shiftPlanning->starting_hour instanceof Carbon
            ? $shiftPlanning->starting_hour->format('H:i:s')
            : $shiftPlanning->starting_hour;
        $endTime = $shiftPlanning->ending_hour instanceof Carbon
            ? $shiftPlanning->ending_hour->format('H:i:s')
            : $shiftPlanning->ending_hour;

        $availableRooms = $this->conflictService->findAvailableRooms($date, $startTime, $endTime);

        foreach ($availableRooms->take(5) as $room) {
            $proposals[] = [
                'type' => 'change_room',
                'room_id' => $room->id,
                'room_name' => $room->name,
                'room_building' => $room->building,
                'room_floor' => $room->floor,
                'room_capacity' => $room->capacity,
                'description' => "Changer la salle pour {$room->name} ({$room->building})",
            ];
        }

        if (! empty($proposals)) {
            $this->proposals['room_alternatives'] = $proposals;
        }

        return $proposals;
    }

    public function proposeAlternativeTeachers(ShiftPlanning $shiftPlanning, array $conflict): array
    {
        $proposals = [];
        $date = $shiftPlanning->date instanceof Carbon
            ? $shiftPlanning->date->format('Y-m-d')
            : $shiftPlanning->date;
        $startTime = $shiftPlanning->starting_hour instanceof Carbon
            ? $shiftPlanning->starting_hour->format('H:i:s')
            : $shiftPlanning->starting_hour;
        $endTime = $shiftPlanning->ending_hour instanceof Carbon
            ? $shiftPlanning->ending_hour->format('H:i:s')
            : $shiftPlanning->ending_hour;

        if ($shiftPlanning->course_id) {
            $courseTeachers = Teacher::whereHas('courses', function ($q) use ($shiftPlanning) {
                $q->where('courses.id', $shiftPlanning->course_id);
            })->get();

            $availableTeachers = $this->conflictService->findAvailableTeachers($date, $startTime, $endTime);

            $qualifiedAvailable = $courseTeachers->filter(function ($teacher) use ($availableTeachers) {
                return $availableTeachers->contains('id', $teacher->id);
            });

            foreach ($qualifiedAvailable->take(5) as $teacher) {
                $proposals[] = [
                    'type' => 'change_teacher',
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->full_name,
                    'teacher_speciality' => $teacher->speciality,
                    'description' => "Changer le professeur pour {$teacher->full_name}",
                ];
            }
        }

        if (empty($proposals)) {
            $availableTeachers = $this->conflictService->findAvailableTeachers($date, $startTime, $endTime);
            foreach ($availableTeachers->take(5) as $teacher) {
                $proposals[] = [
                    'type' => 'change_teacher',
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->full_name,
                    'teacher_speciality' => $teacher->speciality,
                    'description' => "Changer le professeur pour {$teacher->full_name}",
                ];
            }
        }

        if (! empty($proposals)) {
            $this->proposals['teacher_alternatives'] = $proposals;
        }

        return $proposals;
    }

    public function proposeAlternativeTimeSlots(ShiftPlanning $shiftPlanning, array $conflict): array
    {
        $proposals = [];
        $date = $shiftPlanning->date instanceof Carbon
            ? $shiftPlanning->date->format('Y-m-d')
            : $shiftPlanning->date;

        $standardSlots = $this->getStandardTimeSlots();

        foreach ($standardSlots as $slot) {
            $startTime = $slot['start'];
            $endTime = $slot['end'];

            $hasRoomConflict = $this->conflictService->checkRoomConflict(
                $this->createTemporaryShiftPlanning($shiftPlanning, $date, $startTime, $endTime)
            ) === null;

            $hasTeacherConflict = $this->conflictService->checkTeacherConflict(
                $this->createTemporaryShiftPlanning($shiftPlanning, $date, $startTime, $endTime)
            ) === null;

            if ($hasRoomConflict && $hasTeacherConflict) {
                $proposals[] = [
                    'type' => 'change_time',
                    'date' => $date,
                    'starting_hour' => $startTime,
                    'ending_hour' => $endTime,
                    'description' => "Déplacer le cours au {$slot['label']}",
                ];
            }

            if (count($proposals) >= 3) {
                break;
            }
        }

        if (! empty($proposals)) {
            $this->proposals['time_alternatives'] = $proposals;
        }

        return $proposals;
    }

    public function proposeResolution(ShiftPlanning $shiftPlanning, array $conflict): ?array
    {
        $proposals = $this->getProposalsForConflicts([$conflict], $shiftPlanning);

        $allProposals = array_merge(
            $proposals['room_alternatives'] ?? [],
            $proposals['teacher_alternatives'] ?? [],
            $proposals['time_alternatives'] ?? []
        );

        return $allProposals[0] ?? null;
    }

    public function autoResolve(ShiftPlanning $shiftPlanning): ?ShiftPlanning
    {
        $conflicts = $this->conflictService->detectConflicts($shiftPlanning);

        if (empty($conflicts)) {
            return $shiftPlanning;
        }

        foreach ($conflicts as $conflict) {
            $proposal = $this->proposeResolution($shiftPlanning, $conflict);

            if (! $proposal) {
                continue;
            }

            $tempShift = clone $shiftPlanning;

            switch ($proposal['type']) {
                case 'change_room':
                    $tempShift->room_id = $proposal['room_id'];
                    break;
                case 'change_teacher':
                    $tempShift->teacher_id = $proposal['teacher_id'];
                    break;
                case 'change_time':
                    $tempShift->starting_hour = $proposal['starting_hour'];
                    $tempShift->ending_hour = $proposal['ending_hour'];
                    break;
            }

            $newConflicts = $this->conflictService->detectConflicts($tempShift, $shiftPlanning->id);

            if (empty($newConflicts)) {
                $shiftPlanning->fill([
                    'room_id' => $tempShift->room_id,
                    'teacher_id' => $tempShift->teacher_id,
                    'starting_hour' => $tempShift->starting_hour,
                    'ending_hour' => $tempShift->ending_hour,
                ]);
                $shiftPlanning->save();

                return $shiftPlanning;
            }
        }

        return null;
    }

    protected function createTemporaryShiftPlanning(ShiftPlanning $shiftPlanning, string $date, string $startTime, string $endTime): ShiftPlanning
    {
        $temp = new ShiftPlanning;
        $temp->room_id = $shiftPlanning->room_id;
        $temp->teacher_id = $shiftPlanning->teacher_id;
        $temp->course_class_id = $shiftPlanning->course_class_id;
        $temp->course_id = $shiftPlanning->course_id;
        $temp->date = $date;
        $temp->starting_hour = $startTime;
        $temp->ending_hour = $endTime;

        return $temp;
    }

    protected function getStandardTimeSlots(): array
    {
        return [
            ['start' => '08:00', 'end' => '09:00', 'label' => '8h00 - 9h00'],
            ['start' => '09:00', 'end' => '10:00', 'label' => '9h00 - 10h00'],
            ['start' => '10:00', 'end' => '11:00', 'label' => '10h00 - 11h00'],
            ['start' => '11:00', 'end' => '12:00', 'label' => '11h00 - 12h00'],
            ['start' => '13:00', 'end' => '14:00', 'label' => '13h00 - 14h00'],
            ['start' => '14:00', 'end' => '15:00', 'label' => '14h00 - 15h00'],
            ['start' => '15:00', 'end' => '16:00', 'label' => '15h00 - 16h00'],
            ['start' => '16:00', 'end' => '17:00', 'label' => '16h00 - 17h00'],
            ['start' => '17:00', 'end' => '18:00', 'label' => '17h00 - 18h00'],
        ];
    }

    public function findBestSlot(CourseClass $class, int $courseId, int $teacherId, string $date): ?array
    {
        $standardSlots = $this->getStandardTimeSlots();

        foreach ($standardSlots as $slot) {
            $tempPlanning = new ShiftPlanning;
            $tempPlanning->course_class_id = $class->id;
            $tempPlanning->course_id = $courseId;
            $tempPlanning->teacher_id = $teacherId;
            $tempPlanning->date = $date;
            $tempPlanning->starting_hour = $slot['start'];
            $tempPlanning->ending_hour = $slot['end'];

            $conflicts = $this->conflictService->detectConflicts($tempPlanning);

            if (empty($conflicts)) {
                return $slot;
            }
        }

        return null;
    }

    public function getProposals(): array
    {
        return $this->proposals;
    }
}
