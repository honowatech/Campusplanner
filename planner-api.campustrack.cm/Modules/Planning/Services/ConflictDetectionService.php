<?php

namespace Modules\Planning\Services;

use App\Models\Room;
use App\Models\RoomBlocking;
use App\Models\Teacher;
use App\Models\TeacherBlocking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Planning\Entities\Planning;
use Modules\Planning\Entities\ShiftPlanning;

class ConflictDetectionService
{
    public const TYPE_ROOM_CONFLICT = 'room_conflict';

    public const TYPE_TEACHER_CONFLICT = 'teacher_conflict';

    public const TYPE_CLASS_CONFLICT = 'class_conflict';

    public const TYPE_ROOM_BLOCKING = 'room_blocking';

    public const TYPE_TEACHER_BLOCKING = 'teacher_blocking';

    public const TYPE_HOURS_EXCEEDED = 'hours_exceeded';

    protected array $conflicts = [];

    public function detectConflicts(ShiftPlanning $shiftPlanning, ?int $excludeId = null): array
    {
        $this->conflicts = [];

        $this->checkRoomConflict($shiftPlanning, $excludeId);
        $this->checkTeacherConflict($shiftPlanning, $excludeId);
        $this->checkClassConflict($shiftPlanning, $excludeId);
        $this->checkRoomBlocking($shiftPlanning);
        $this->checkTeacherBlocking($shiftPlanning);
        $this->checkTeacherHoursLimit($shiftPlanning);

        return $this->conflicts;
    }

    public function checkRoomConflict(ShiftPlanning $shiftPlanning, ?int $excludeId = null): ?array
    {
        if (! $shiftPlanning->room_id || ! $shiftPlanning->date) {
            return null;
        }

        $query = ShiftPlanning::where('room_id', $shiftPlanning->room_id)
            ->where('date', $shiftPlanning->date)
            ->where(function ($q) use ($shiftPlanning) {
                $q->where(function ($q2) use ($shiftPlanning) {
                    $q2->where('starting_hour', '<', $shiftPlanning->ending_hour)
                        ->where('ending_hour', '>', $shiftPlanning->starting_hour);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflictingPlannings = $query->get();

        if ($conflictingPlannings->isNotEmpty()) {
            $conflict = [
                'type' => self::TYPE_ROOM_CONFLICT,
                'message' => 'La salle est déjà occupée pendant ce créneau',
                'room_id' => $shiftPlanning->room_id,
                'conflicting_plannings' => $conflictingPlannings->pluck('id')->toArray(),
            ];
            $this->conflicts[] = $conflict;

            return $conflict;
        }

        return null;
    }

    public function checkTeacherConflict(ShiftPlanning $shiftPlanning, ?int $excludeId = null): ?array
    {
        if (! $shiftPlanning->teacher_id || ! $shiftPlanning->date) {
            return null;
        }

        $query = ShiftPlanning::where('teacher_id', $shiftPlanning->teacher_id)
            ->where('date', $shiftPlanning->date)
            ->where(function ($q) use ($shiftPlanning) {
                $q->where(function ($q2) use ($shiftPlanning) {
                    $q2->where('starting_hour', '<', $shiftPlanning->ending_hour)
                        ->where('ending_hour', '>', $shiftPlanning->starting_hour);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflictingPlannings = $query->get();

        if ($conflictingPlannings->isNotEmpty()) {
            $conflict = [
                'type' => self::TYPE_TEACHER_CONFLICT,
                'message' => 'Le professeur est déjà assigné à un autre cours pendant ce créneau',
                'teacher_id' => $shiftPlanning->teacher_id,
                'conflicting_plannings' => $conflictingPlannings->pluck('id')->toArray(),
            ];
            $this->conflicts[] = $conflict;

            return $conflict;
        }

        return null;
    }

    public function checkClassConflict(ShiftPlanning $shiftPlanning, ?int $excludeId = null): ?array
    {
        if (! $shiftPlanning->course_class_id || ! $shiftPlanning->date) {
            return null;
        }

        $query = ShiftPlanning::where('course_class_id', $shiftPlanning->course_class_id)
            ->where('date', $shiftPlanning->date)
            ->where(function ($q) use ($shiftPlanning) {
                $q->where(function ($q2) use ($shiftPlanning) {
                    $q2->where('starting_hour', '<', $shiftPlanning->ending_hour)
                        ->where('ending_hour', '>', $shiftPlanning->starting_hour);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflictingPlannings = $query->get();

        if ($conflictingPlannings->isNotEmpty()) {
            $conflict = [
                'type' => self::TYPE_CLASS_CONFLICT,
                'message' => 'La classe a déjà un cours pendant ce créneau',
                'course_class_id' => $shiftPlanning->course_class_id,
                'conflicting_plannings' => $conflictingPlannings->pluck('id')->toArray(),
            ];
            $this->conflicts[] = $conflict;

            return $conflict;
        }

        return null;
    }

    public function checkRoomBlocking(ShiftPlanning $shiftPlanning): ?array
    {
        if (! $shiftPlanning->room_id || ! $shiftPlanning->date) {
            return null;
        }

        $dateValue = $shiftPlanning->date instanceof Carbon
            ? $shiftPlanning->date->format('Y-m-d')
            : $shiftPlanning->date;
        $startTime = $shiftPlanning->starting_hour instanceof Carbon
            ? $shiftPlanning->starting_hour->format('H:i:s')
            : $shiftPlanning->starting_hour;
        $endTime = $shiftPlanning->ending_hour instanceof Carbon
            ? $shiftPlanning->ending_hour->format('H:i:s')
            : $shiftPlanning->ending_hour;

        $startDateTime = $dateValue.' '.$startTime;
        $endDateTime = $dateValue.' '.$endTime;

        $blocking = RoomBlocking::where('room_id', $shiftPlanning->room_id)
            ->approved()
            ->forPeriod($startDateTime, $endDateTime)
            ->first();

        if ($blocking) {
            $conflict = [
                'type' => self::TYPE_ROOM_BLOCKING,
                'message' => 'La salle est bloquée: '.$blocking->reason,
                'room_id' => $shiftPlanning->room_id,
                'blocking_id' => $blocking->id,
                'blocking_reason' => $blocking->reason,
            ];
            $this->conflicts[] = $conflict;

            return $conflict;
        }

        return null;
    }

    public function checkTeacherBlocking(ShiftPlanning $shiftPlanning): ?array
    {
        if (! $shiftPlanning->teacher_id || ! $shiftPlanning->date) {
            return null;
        }

        $dateValue = $shiftPlanning->date instanceof Carbon
            ? $shiftPlanning->date->format('Y-m-d')
            : $shiftPlanning->date;
        $startTime = $shiftPlanning->starting_hour instanceof Carbon
            ? $shiftPlanning->starting_hour->format('H:i:s')
            : $shiftPlanning->starting_hour;
        $endTime = $shiftPlanning->ending_hour instanceof Carbon
            ? $shiftPlanning->ending_hour->format('H:i:s')
            : $shiftPlanning->ending_hour;

        $startDateTime = $dateValue.' '.$startTime;
        $endDateTime = $dateValue.' '.$endTime;

        $blocking = TeacherBlocking::where('teacher_id', $shiftPlanning->teacher_id)
            ->approved()
            ->forPeriod($startDateTime, $endDateTime)
            ->first();

        if ($blocking) {
            $conflict = [
                'type' => self::TYPE_TEACHER_BLOCKING,
                'message' => 'Le professeur est indisponible: '.$blocking->reason,
                'teacher_id' => $shiftPlanning->teacher_id,
                'blocking_id' => $blocking->id,
                'blocking_reason' => $blocking->reason,
            ];
            $this->conflicts[] = $conflict;

            return $conflict;
        }

        return null;
    }

    public function checkTeacherHoursLimit(ShiftPlanning $shiftPlanning): ?array
    {
        if (! $shiftPlanning->teacher_id || ! $shiftPlanning->date) {
            return null;
        }

        $teacher = Teacher::find($shiftPlanning->teacher_id);
        if (! $teacher || ! $teacher->max_hours_per_week) {
            return null;
        }

        $dateValue = $shiftPlanning->date instanceof Carbon
            ? $shiftPlanning->date
            : Carbon::parse($shiftPlanning->date);

        $weekStart = $dateValue->startOfWeek()->format('Y-m-d');
        $weekEnd = $dateValue->endOfWeek()->format('Y-m-d');

        $hoursNeeded = $this->calculateHours($shiftPlanning->starting_hour, $shiftPlanning->ending_hour);

        $currentHours = ShiftPlanning::where('teacher_id', $shiftPlanning->teacher_id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->where('status', '!=', 'canceled')
            ->get()
            ->sum(function ($sp) {
                return $this->calculateHours($sp->starting_hour, $sp->ending_hour);
            });

        if (($currentHours + $hoursNeeded) > $teacher->max_hours_per_week) {
            $conflict = [
                'type' => self::TYPE_HOURS_EXCEEDED,
                'message' => 'Le professeur dépasse son quota d\'heures hebdomadaire',
                'teacher_id' => $shiftPlanning->teacher_id,
                'current_hours' => $currentHours,
                'hours_needed' => $hoursNeeded,
                'max_hours' => $teacher->max_hours_per_week,
            ];
            $this->conflicts[] = $conflict;

            return $conflict;
        }

        return null;
    }

    public function detectAllConflictsForPlanning(Planning $planning): array
    {
        $allConflicts = [];

        $shiftPlannings = ShiftPlanning::where('planning_id', $planning->id)
            ->with(['room', 'teacher', 'courseClass', 'course'])
            ->get();

        foreach ($shiftPlannings as $shiftPlanning) {
            $conflicts = $this->detectConflicts($shiftPlanning, $shiftPlanning->id);
            if (! empty($conflicts)) {
                $allConflicts[] = [
                    'shift_planning_id' => $shiftPlanning->id,
                    'shift_planning' => $shiftPlanning,
                    'conflicts' => $conflicts,
                ];
            }
        }

        return $allConflicts;
    }

    public function findAvailableRooms(string $date, string $startTime, string $endTime, ?int $excludeId = null): Collection
    {
        $occupiedRoomIds = ShiftPlanning::where('date', $date)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('starting_hour', '<', $endTime)
                    ->where('ending_hour', '>', $startTime);
            })
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->pluck('room_id')
            ->filter()
            ->unique()
            ->toArray();

        $blockedRoomIds = RoomBlocking::where(function ($q) use ($date, $startTime, $endTime) {
            $q->where('start_datetime', '<', $date.' '.$endTime)
                ->where('end_datetime', '>', $date.' '.$startTime);
        })
            ->approved()
            ->pluck('room_id')
            ->filter()
            ->unique()
            ->toArray();

        $unavailableIds = array_unique(array_merge($occupiedRoomIds, $blockedRoomIds));

        return Room::whereNotIn('id', $unavailableIds)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function findAvailableTeachers(string $date, string $startTime, string $endTime, ?int $excludeId = null): Collection
    {
        $occupiedTeacherIds = ShiftPlanning::where('date', $date)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('starting_hour', '<', $endTime)
                    ->where('ending_hour', '>', $startTime);
            })
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->toArray();

        $blockedTeacherIds = TeacherBlocking::where(function ($q) use ($date, $startTime, $endTime) {
            $q->where('start_datetime', '<', $date.' '.$endTime)
                ->where('end_datetime', '>', $date.' '.$startTime);
        })
            ->approved()
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->toArray();

        $unavailableIds = array_unique(array_merge($occupiedTeacherIds, $blockedTeacherIds));

        return Teacher::whereNotIn('id', $unavailableIds)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
    }

    protected function calculateHours($startTime, $endTime): float
    {
        $start = $startTime instanceof Carbon ? $startTime->format('H:i:s') : $startTime;
        $end = $endTime instanceof Carbon ? $endTime->format('H:i:s') : $endTime;

        $startTimestamp = strtotime($start);
        $endTimestamp = strtotime($end);

        return round(($endTimestamp - $startTimestamp) / 3600, 2);
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function hasConflicts(): bool
    {
        return ! empty($this->conflicts);
    }
}
