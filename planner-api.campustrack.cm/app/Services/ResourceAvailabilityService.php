<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceAvailabilityService
{
    /**
     * Check if the shift plannings table exists.
     */
    private function shiftPlanningsTableExists(): bool
    {
        return Schema::hasTable('planning_shift_plannings');
    }

    /**
     * Find available rooms for a given time slot.
     */
    public function findAvailableRooms(
        string $startDatetime,
        string $endDatetime,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        $query = Room::query()
            ->where('is_active', true);

        // Apply filters
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['min_capacity'])) {
            $query->where('capacity', '>=', $filters['min_capacity']);
        }

        if (! empty($filters['has_projector'])) {
            $query->where('has_projector', true);
        }

        if (! empty($filters['has_computers'])) {
            $query->where('has_computers', true);
        }

        if (! empty($filters['has_whiteboard'])) {
            $query->where('has_whiteboard', true);
        }

        // Exclude rooms with blockings in the requested period
        $query->whereDoesntHave('blockings', function (Builder $q) use ($start, $end) {
            $q->where('start_datetime', '<', $end)
                ->where('end_datetime', '>', $start);
        });

        // Exclude rooms with shift plannings in the requested period (if table exists)
        if ($this->shiftPlanningsTableExists()) {
            $query->whereDoesntHave('shiftPlannings', function (Builder $q) use ($start, $end) {
                $q->where('date', $start->format('Y-m-d'))
                    ->where(function (Builder $sq) use ($start, $end) {
                        $sq->whereBetween('starting_hour', [
                            $start->format('H:i:s'),
                            $end->format('H:i:s'),
                        ])->orWhereBetween('ending_hour', [
                            $start->format('H:i:s'),
                            $end->format('H:i:s'),
                        ])->orWhere(function (Builder $tq) use ($start, $end) {
                            $tq->where('starting_hour', '<=', $start->format('H:i:s'))
                                ->where('ending_hour', '>=', $end->format('H:i:s'));
                        });
                    });
            });
        }

        return $query->with(['department'])
            ->paginate($perPage);
    }

    /**
     * Find available teachers for a given time slot.
     */
    public function findAvailableTeachers(
        string $startDatetime,
        string $endDatetime,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        $query = Teacher::query()
            ->where('is_active', true);

        // Apply filters
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['course_id'])) {
            $query->whereHas('courses', function (Builder $q) use ($filters) {
                $q->where('courses.id', $filters['course_id']);
            });
        }

        if (! empty($filters['speciality'])) {
            $query->where('speciality', 'like', '%'.$filters['speciality'].'%');
        }

        // Exclude teachers with approved blockings in the requested period
        $query->whereDoesntHave('blockings', function (Builder $q) use ($start, $end) {
            $q->where('status', 'approved')
                ->where('start_datetime', '<', $end)
                ->where('end_datetime', '>', $start);
        });

        // Exclude teachers with shift plannings in the requested period (if table exists)
        if ($this->shiftPlanningsTableExists()) {
            $query->whereDoesntHave('shiftPlannings', function (Builder $q) use ($start, $end) {
                $q->where('date', $start->format('Y-m-d'))
                    ->where(function (Builder $sq) use ($start, $end) {
                        $sq->whereBetween('starting_hour', [
                            $start->format('H:i:s'),
                            $end->format('H:i:s'),
                        ])->orWhereBetween('ending_hour', [
                            $start->format('H:i:s'),
                            $end->format('H:i:s'),
                        ])->orWhere(function (Builder $tq) use ($start, $end) {
                            $tq->where('starting_hour', '<=', $start->format('H:i:s'))
                                ->where('ending_hour', '>=', $end->format('H:i:s'));
                        });
                    });
            });

            // Check weekly hour limit if needed
            if (! empty($filters['check_hours'])) {
                $weekStart = $start->copy()->startOfWeek();
                $weekEnd = $start->copy()->endOfWeek();
                $requestedHours = $end->diffInHours($start);

                $query->where(function (Builder $q) use ($weekStart, $weekEnd, $requestedHours) {
                    $q->whereNull('max_hours_per_week')
                        ->orWhereHas('shiftPlannings', function (Builder $sq) use ($weekStart, $weekEnd, $requestedHours) {
                            $hoursExpr = hours_diff_expr()->getValue(DB::connection()->getQueryGrammar());
                            $sq->selectRaw('SUM('.$hoursExpr.') as total_hours')
                                ->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                                ->havingRaw('total_hours + ? <= max_hours_per_week', [$requestedHours]);
                        }, '<=', 1);
                });
            }
        }

        return $query->with(['department', 'courses'])
            ->paginate($perPage);
    }

    /**
     * Get alternative suggestions when resources are not available.
     *
     * @param  string  $resourceType  'room' | 'teacher'
     */
    public function suggestAlternatives(
        string $resourceType,
        ?int $resourceId,
        string $startDatetime,
        string $endDatetime,
        array $filters = [],
        int $limit = 5
    ): Collection {
        if ($resourceType === 'room') {
            return $this->findAvailableRooms($startDatetime, $endDatetime, $filters, $limit)
                ->getCollection();
        }

        if ($resourceType === 'teacher') {
            return $this->findAvailableTeachers($startDatetime, $endDatetime, $filters, $limit)
                ->getCollection();
        }

        return collect();
    }

    /**
     * Check if a room is available (wrapper method).
     */
    public function isRoomAvailable(
        int $roomId,
        string $startDatetime,
        string $endDatetime,
        ?int $excludePlanningId = null
    ): bool {
        $room = Room::find($roomId);

        if (! $room) {
            return false;
        }

        return $room->isAvailable($startDatetime, $endDatetime, $excludePlanningId);
    }

    /**
     * Check if a teacher is available.
     */
    public function isTeacherAvailable(
        int $teacherId,
        string $startDatetime,
        string $endDatetime
    ): bool {
        $teacher = Teacher::find($teacherId);

        if (! $teacher || ! $teacher->is_active) {
            return false;
        }

        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        // Check for approved blockings
        $hasBlocking = $teacher->blockings()
            ->where('status', 'approved')
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();

        if ($hasBlocking) {
            return false;
        }

        // Check for existing plannings (if table exists)
        if ($this->shiftPlanningsTableExists()) {
            $hasPlanning = $teacher->shiftPlannings()
                ->where('date', $start->format('Y-m-d'))
                ->where(function (Builder $q) use ($start, $end) {
                    $q->whereBetween('starting_hour', [
                        $start->format('H:i:s'),
                        $end->format('H:i:s'),
                    ])->orWhereBetween('ending_hour', [
                        $start->format('H:i:s'),
                        $end->format('H:i:s'),
                    ])->orWhere(function (Builder $tq) use ($start, $end) {
                        $tq->where('starting_hour', '<=', $start->format('H:i:s'))
                            ->where('ending_hour', '>=', $end->format('H:i:s'));
                    });
                })
                ->exists();

            return ! $hasPlanning;
        }

        return true;
    }
}
