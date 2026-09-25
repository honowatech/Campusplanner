<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Department;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherBlocking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    const CACHE_REALTIME_TTL = 300;    // 5 minutes

    const CACHE_HEAVY_TTL = 3600;      // 1 heure

    const CACHE_PREFIX = 'dashboard:';

    /**
     * Get cache key
     */
    private function getCacheKey(string $type, string $method, string $period, ?int $deptId): string
    {
        $deptSuffix = $deptId ? ":dept{$deptId}" : ':all';

        return self::CACHE_PREFIX."{$type}:{$method}:{$period}{$deptSuffix}";
    }

    /**
     * Clear cache
     */
    public function clearCache(?string $pattern = null): void
    {
        try {
            Cache::flush();
        } catch (\Exception $e) {
            logger()->error('Dashboard cache clear error: '.$e->getMessage());
        }
    }

    // ==================== REALTIME METHODS (5 min cache) ====================

    /**
     * Get overview realtime stats
     */
    public function getOverviewRealtime(?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('realtime', 'overview', 'now', $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_REALTIME_TTL, function () use ($departmentId) {
            return $this->calculateOverviewRealtime($departmentId);
        });
    }

    /**
     * Get current sessions
     */
    public function getCurrentSessions(?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('realtime', 'sessions', 'now', $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_REALTIME_TTL, function () use ($departmentId) {
            return $this->calculateCurrentSessions($departmentId);
        });
    }

    /**
     * Get alerts
     */
    public function getAlerts(?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('realtime', 'alerts', 'now', $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_REALTIME_TTL, function () use ($departmentId) {
            return $this->calculateAlerts($departmentId);
        });
    }

    // ==================== HEAVY METHODS (1h cache) ====================

    /**
     * Get overview heavy stats
     */
    public function getOverviewHeavy(string $period, ?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('heavy', 'overview', $period, $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_HEAVY_TTL, function () use ($period, $departmentId) {
            return $this->calculateOverviewHeavy($period, $departmentId);
        });
    }

    /**
     * Get department stats
     */
    public function getDepartmentStats(string $period): array
    {
        $cacheKey = $this->getCacheKey('heavy', 'departments', $period, null);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_HEAVY_TTL, function () use ($period) {
            return $this->calculateDepartmentStats($period);
        });
    }

    /**
     * Get resource utilization
     */
    public function getResourceUtilization(string $period, ?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('heavy', 'resources', $period, $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_HEAVY_TTL, function () use ($period, $departmentId) {
            return $this->calculateResourceUtilization($period, $departmentId);
        });
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(string $period, ?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('heavy', 'activity', $period, $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_HEAVY_TTL, function () use ($period, $departmentId) {
            return $this->calculateRecentActivity($period, $departmentId);
        });
    }

    /**
     * Get charts data
     */
    public function getChartsData(string $period, ?int $departmentId): array
    {
        $cacheKey = $this->getCacheKey('heavy', 'charts', $period, $departmentId);

        return Cache::store(config('dashboard.cache.driver', 'redis'))->remember($cacheKey, self::CACHE_HEAVY_TTL, function () use ($period, $departmentId) {
            return $this->calculateChartsData($period, $departmentId);
        });
    }

    // ==================== CALCULATION METHODS ====================

    private function calculateOverviewRealtime(?int $departmentId): array
    {
        $now = Carbon::now();

        // Base queries with optional department filter
        $teacherQuery = Teacher::query();
        $roomQuery = Room::query();
        $classQuery = CourseClass::query();

        if ($departmentId) {
            $teacherQuery->where('department_id', $departmentId);
            $roomQuery->where('department_id', $departmentId);
            $classQuery->where('department_id', $departmentId);
        }

        // Currently active teachers (with sessions now)
        $activeTeachers = $teacherQuery->clone()
            ->whereHas('shiftPlannings', function ($q) use ($now) {
                $q->where('date', $now->format('Y-m-d'))
                    ->where('starting_hour', '<=', $now->format('H:i:s'))
                    ->where('ending_hour', '>=', $now->format('H:i:s'));
            })
            ->count();

        // Currently occupied rooms
        $occupiedRooms = $roomQuery->clone()
            ->whereHas('shiftPlannings', function ($q) use ($now) {
                $q->where('date', $now->format('Y-m-d'))
                    ->where('starting_hour', '<=', $now->format('H:i:s'))
                    ->where('ending_hour', '>=', $now->format('H:i:s'));
            })
            ->count();

        // Classes in session
        $activeClasses = $classQuery->clone()
            ->whereHas('shiftPlannings', function ($q) use ($now) {
                $q->where('date', $now->format('Y-m-d'))
                    ->where('starting_hour', '<=', $now->format('H:i:s'))
                    ->where('ending_hour', '>=', $now->format('H:i:s'));
            })
            ->count();

        // Pending blocking requests
        $pendingBlockings = TeacherBlocking::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('teacher', function ($tq) use ($departmentId) {
                    $tq->where('department_id', $departmentId);
                });
            })
            ->where('status', 'pending')
            ->count();

        return [
            'active_teachers' => $activeTeachers,
            'occupied_rooms' => $occupiedRooms,
            'active_classes' => $activeClasses,
            'pending_blockings' => $pendingBlockings,
            'schedule_conflicts' => $this->countScheduleConflicts($departmentId),
            'updated_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Nombre de cours planifiés impliqués dans au moins un chevauchement
     * (même jour, créneaux qui se recouvrent, même enseignant / même salle /
     * même classe). SQL portable MySQL/SQLite, sans TIME_TO_SEC.
     */
    private function countScheduleConflicts(?int $departmentId): int
    {
        $conflicts = DB::select('
            SELECT COUNT(DISTINCT s.id) AS conflicts
            FROM planning_shift_plannings s
            JOIN planning_shift_plannings o
              ON s.id <> o.id
             AND s.date = o.date
             AND s.starting_hour < o.ending_hour
             AND s.ending_hour > o.starting_hour
             AND (
                   (s.teacher_id IS NOT NULL AND s.teacher_id = o.teacher_id)
                OR (s.room_id IS NOT NULL AND s.room_id = o.room_id)
                OR s.course_class_id = o.course_class_id
             )
            '.($departmentId ? '
            JOIN courses cs ON cs.id = s.course_id AND cs.department_id = ?' : ''),
            $departmentId ? [$departmentId] : []
        );

        return (int) ($conflicts[0]->conflicts ?? 0);
    }

    private function calculateCurrentSessions(?int $departmentId): array
    {
        $now = Carbon::now();

        $sessions = DB::table('planning_shift_plannings')
            ->where('date', $now->format('Y-m-d'))
            ->where('starting_hour', '<=', $now->format('H:i:s'))
            ->where('ending_hour', '>=', $now->format('H:i:s'))
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('class', function ($cq) use ($departmentId) {
                    $cq->where('department_id', $departmentId);
                });
            })
            ->limit(20)
            ->get();

        return [
            'sessions' => $sessions,
            'count' => $sessions->count(),
            'updated_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    private function calculateAlerts(?int $departmentId): array
    {
        $alerts = [
            'critical' => [],
            'warnings' => [],
            'info' => [],
        ];

        $thresholds = config('dashboard.alerts');

        // Critical: Classes over capacity
        $overCapacityClasses = CourseClass::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->withCount('students')
            ->get()
            ->filter(function ($class) use ($thresholds) {
                return $class->students_count > $class->capacity * $thresholds['class_capacity_threshold'];
            });

        foreach ($overCapacityClasses as $class) {
            $alerts['critical'][] = [
                'type' => 'class_over_capacity',
                'severity' => 'critical',
                'message' => "La classe '{$class->name}' dépasse la capacité ({$class->students_count}/{$class->capacity})",
                'entity_id' => $class->id,
                'entity_type' => 'CourseClass',
                'created_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        // Critical: Teachers exceeding max hours
        $teachersQuery = Teacher::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->whereNotNull('max_hours_per_week');

        foreach ($teachersQuery->get() as $teacher) {
            $currentHours = $teacher->shiftPlannings()
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum(hours_diff_expr());

            if ($currentHours > ($teacher->max_hours_per_week * $thresholds['teacher_hours_threshold'])) {
                $alerts['critical'][] = [
                    'type' => 'teacher_over_hours',
                    'severity' => 'critical',
                    'message' => "L'enseignant {$teacher->full_name} approche du maximum d'heures ({$currentHours}/{$teacher->max_hours_per_week})",
                    'entity_id' => $teacher->id,
                    'entity_type' => 'Teacher',
                    'created_at' => now()->format('Y-m-d\TH:i:s\Z'),
                ];
            }
        }

        // Warning: Pending blockings
        $oldPendingBlockings = TeacherBlocking::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('teacher', function ($tq) use ($departmentId) {
                    $tq->where('department_id', $departmentId);
                });
            })
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays($thresholds['pending_blockings_days']))
            ->get();

        foreach ($oldPendingBlockings as $blocking) {
            $alerts['warnings'][] = [
                'type' => 'pending_blocking_old',
                'severity' => 'warning',
                'message' => "Demande de blocage en attente depuis +{$thresholds['pending_blockings_days']} jours",
                'entity_id' => $blocking->id,
                'entity_type' => 'TeacherBlocking',
                'created_at' => $blocking->created_at->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        // Info: Teachers without courses
        $teachersWithoutCourses = Teacher::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->whereDoesntHave('courses')
            ->where('is_active', true)
            ->get();

        foreach ($teachersWithoutCourses as $teacher) {
            $alerts['info'][] = [
                'type' => 'teacher_no_courses',
                'severity' => 'info',
                'message' => "L'enseignant {$teacher->full_name} n'a aucun cours assigné",
                'entity_id' => $teacher->id,
                'entity_type' => 'Teacher',
                'created_at' => now()->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        return $alerts;
    }

    private function calculateOverviewHeavy(string $period, ?int $departmentId): array
    {
        $dates = $this->getPeriodDates($period);

        // Base queries
        $userQuery = User::query();
        $teacherQuery = Teacher::query();
        $studentQuery = Student::query();
        $courseQuery = Course::query();
        $roomQuery = Room::query();
        $classQuery = CourseClass::query();

        if ($departmentId) {
            $userQuery->where('department_id', $departmentId);
            $teacherQuery->where('department_id', $departmentId);
            $studentQuery->whereHas('class', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
            $courseQuery->where('department_id', $departmentId);
            $roomQuery->where('department_id', $departmentId);
            $classQuery->where('department_id', $departmentId);
        }

        // Users stats
        $totalUsers = $userQuery->count();
        $newUsers = $userQuery->clone()
            ->where('created_at', '>=', $dates['start'])
            ->count();

        // Teachers stats
        $totalTeachers = $teacherQuery->count();
        $activeTeachers = $teacherQuery->clone()->where('is_active', true)->count();
        $newTeachers = $teacherQuery->clone()
            ->where('hired_at', '>=', $dates['start'])
            ->count();

        // Students stats
        $totalStudents = $studentQuery->count();
        $newStudents = $studentQuery->clone()
            ->where('admission_date', '>=', $dates['start'])
            ->count();

        // Resources
        $totalCourses = $courseQuery->count();
        $totalRooms = $roomQuery->count();
        $totalClasses = $classQuery->count();

        // Planning stats
        $planningStats = $this->getPlanningStats($dates, $departmentId);

        return [
            'users' => [
                'total' => $totalUsers,
                'new_this_period' => $newUsers,
            ],
            'teachers' => [
                'total' => $totalTeachers,
                'active' => $activeTeachers,
                'new_this_period' => $newTeachers,
            ],
            'students' => [
                'total' => $totalStudents,
                'new_this_period' => $newStudents,
            ],
            'resources' => [
                'courses' => $totalCourses,
                'rooms' => $totalRooms,
                'classes' => $totalClasses,
            ],
            'planning' => $planningStats,
        ];
    }

    private function calculateDepartmentStats(string $period): array
    {
        $departments = Department::withCount([
            'teachers',
            'courses',
            'rooms',
            'courseClasses as classes_count',
        ])->get();

        $stats = [];
        foreach ($departments as $dept) {
            // Student count for department
            $studentCount = Student::whereHas('class', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            })->count();

            // Room utilization
            $totalHours = 40; // Assuming 40h week
            $bookedHours = DB::table('planning_shift_plannings')
                ->whereHas('class', function ($q) use ($dept) {
                    $q->where('department_id', $dept->id);
                })
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum(hours_diff_expr());

            $roomUtilization = $dept->rooms_count > 0
                ? round(($bookedHours / ($dept->rooms_count * $totalHours)) * 100, 2)
                : 0;

            // Average teacher hours
            $avgTeacherHours = $dept->teachers_count > 0
                ? round(DB::table('planning_shift_plannings')
                    ->whereHas('class', function ($q) use ($dept) {
                        $q->where('department_id', $dept->id);
                    })
                    ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->sum(hours_diff_expr()) / $dept->teachers_count, 2)
                : 0;

            $stats[] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'code' => $dept->code,
                'stats' => [
                    'teachers' => $dept->teachers_count,
                    'students' => $studentCount,
                    'courses' => $dept->courses_count,
                    'classes' => $dept->classes_count,
                    'rooms' => $dept->rooms_count,
                    'room_utilization' => $roomUtilization,
                    'avg_teacher_hours' => $avgTeacherHours,
                ],
            ];
        }

        return $stats;
    }

    private function calculateResourceUtilization(string $period, ?int $departmentId): array
    {
        $dates = $this->getPeriodDates($period);

        // Rooms utilization
        $roomQuery = Room::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });

        $rooms = $roomQuery->get();
        $roomStats = [];

        foreach ($rooms as $room) {
            $bookedHours = $room->shiftPlannings()
                ->whereBetween('date', [$dates['start'], $dates['end']])
                ->sum(hours_diff_expr());

            $totalHours = $dates['start']->diffInHours($dates['end']);
            $utilization = $totalHours > 0 ? round(($bookedHours / $totalHours) * 100, 2) : 0;

            $roomStats[] = [
                'id' => $room->id,
                'name' => $room->name,
                'type' => $room->type,
                'utilization_rate' => $utilization,
                'booked_hours' => round($bookedHours, 2),
            ];
        }

        // Teachers utilization
        $teacherQuery = Teacher::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('is_active', true);

        $teachers = $teacherQuery->get();
        $teacherStats = [];

        foreach ($teachers as $teacher) {
            $currentHours = $teacher->shiftPlannings()
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum(hours_diff_expr());

            $maxHours = $teacher->max_hours_per_week ?? 40;
            $utilization = $maxHours > 0 ? round(($currentHours / $maxHours) * 100, 2) : 0;

            $teacherStats[] = [
                'id' => $teacher->id,
                'name' => $teacher->full_name,
                'current_hours' => round($currentHours, 2),
                'max_hours' => $maxHours,
                'utilization_rate' => $utilization,
            ];
        }

        return [
            'rooms' => [
                'data' => $roomStats,
                'average_utilization' => round(collect($roomStats)->avg('utilization_rate'), 2),
            ],
            'teachers' => [
                'data' => $teacherStats,
                'average_utilization' => round(collect($teacherStats)->avg('utilization_rate'), 2),
            ],
        ];
    }

    private function calculateRecentActivity(string $period, ?int $departmentId): array
    {
        $dates = $this->getPeriodDates($period);
        $activities = [];

        // Recent teacher blockings
        $blockings = TeacherBlocking::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('teacher', function ($tq) use ($departmentId) {
                    $tq->where('department_id', $departmentId);
                });
            })
            ->where('created_at', '>=', $dates['start'])
            ->with('teacher')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        foreach ($blockings as $blocking) {
            $activities[] = [
                'type' => 'blocking_created',
                'description' => "Demande de blocage créée par {$blocking->teacher->full_name}",
                'status' => $blocking->status,
                'created_at' => $blocking->created_at->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        // Recent student admissions
        $students = Student::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('class', function ($cq) use ($departmentId) {
                    $cq->where('department_id', $departmentId);
                });
            })
            ->where('admission_date', '>=', $dates['start'])
            ->orderBy('admission_date', 'desc')
            ->limit(20)
            ->get();

        foreach ($students as $student) {
            $activities[] = [
                'type' => 'student_admission',
                'description' => "Nouvel étudiant inscrit: {$student->full_name}",
                'created_at' => Carbon::parse($student->admission_date)->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        // Recent teacher hires
        $teachers = Teacher::query()
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('hired_at', '>=', $dates['start'])
            ->orderBy('hired_at', 'desc')
            ->limit(20)
            ->get();

        foreach ($teachers as $teacher) {
            $activities[] = [
                'type' => 'teacher_hired',
                'description' => "Nouvel enseignant embauché: {$teacher->full_name}",
                'created_at' => Carbon::parse($teacher->hired_at)->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        // Sort by date
        usort($activities, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, 50);
    }

    private function calculateChartsData(string $period, ?int $departmentId): array
    {
        $dates = $this->getPeriodDates($period);

        // Evolution chart data
        $labels = [];
        $datasets = [];

        $interval = $period === '24h' ? 'hour' : ($period === '7d' ? 'day' : 'day');
        $points = $period === '24h' ? 24 : ($period === '7d' ? 7 : 30);

        for ($i = $points - 1; $i >= 0; $i--) {
            if ($interval === 'hour') {
                $labels[] = now()->subHours($i)->format('H:00');
            } else {
                $labels[] = now()->subDays($i)->format('d/m');
            }
        }

        // Sessions count per interval
        $sessionsData = [];
        for ($i = $points - 1; $i >= 0; $i--) {
            if ($interval === 'hour') {
                $start = now()->subHours($i + 1);
                $end = now()->subHours($i);
            } else {
                $start = now()->subDays($i + 1)->startOfDay();
                $end = now()->subDays($i)->endOfDay();
            }

            $count = DB::table('planning_shift_plannings')
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->whereHas('class', function ($cq) use ($departmentId) {
                        $cq->where('department_id', $departmentId);
                    });
                })
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->count();

            $sessionsData[] = $count;
        }

        $datasets[] = [
            'label' => 'Séances planifiées',
            'data' => $sessionsData,
        ];

        // Comparison data (current vs previous period)
        $comparisonLabels = [];
        $comparisonDatasets = [];

        // Current period totals
        $currentTotals = [
            'sessions' => DB::table('planning_shift_plannings')
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->whereHas('class', function ($cq) use ($departmentId) {
                        $cq->where('department_id', $departmentId);
                    });
                })
                ->whereBetween('date', [$dates['start'], $dates['end']])
                ->count(),
            'new_students' => Student::when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('class', function ($cq) use ($departmentId) {
                    $cq->where('department_id', $departmentId);
                });
            })
                ->whereBetween('admission_date', [$dates['start'], $dates['end']])
                ->count(),
        ];

        // Previous period totals
        $previousTotals = [
            'sessions' => DB::table('planning_shift_plannings')
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->whereHas('class', function ($cq) use ($departmentId) {
                        $cq->where('department_id', $departmentId);
                    });
                })
                ->whereBetween('date', [$dates['previous_start'], $dates['previous_end']])
                ->count(),
            'new_students' => Student::when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('class', function ($cq) use ($departmentId) {
                    $cq->where('department_id', $departmentId);
                });
            })
                ->whereBetween('admission_date', [$dates['previous_start'], $dates['previous_end']])
                ->count(),
        ];

        return [
            'evolution' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
            'comparison' => [
                'current_period' => $currentTotals,
                'previous_period' => $previousTotals,
                'change_percentage' => [
                    'sessions' => $previousTotals['sessions'] > 0
                        ? round((($currentTotals['sessions'] - $previousTotals['sessions']) / $previousTotals['sessions']) * 100, 2)
                        : 0,
                    'new_students' => $previousTotals['new_students'] > 0
                        ? round((($currentTotals['new_students'] - $previousTotals['new_students']) / $previousTotals['new_students']) * 100, 2)
                        : 0,
                ],
            ],
        ];
    }

    private function getPlanningStats(array $dates, ?int $departmentId): array
    {
        $query = DB::table('planning_shift_plannings')
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('class', function ($cq) use ($departmentId) {
                    $cq->where('department_id', $departmentId);
                });
            })
            ->whereBetween('date', [$dates['start']->format('Y-m-d'), $dates['end']->format('Y-m-d')]);

        $total = $query->count();
        $completed = $query->clone()->where('status', 'completed')->count();
        $ongoing = $query->clone()->where('status', 'ongoing')->count();
        $pending = $query->clone()->where('status', 'pending')->count();
        $canceled = $query->clone()->where('status', 'canceled')->count();

        // Calculate utilization rate
        $totalHours = $dates['start']->diffInHours($dates['end']);
        $roomCount = Room::when($departmentId, function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        })->count();

        $bookedHours = $query->clone()
            ->sum(hours_diff_expr());

        $utilizationRate = ($totalHours > 0 && $roomCount > 0)
            ? round(($bookedHours / ($totalHours * $roomCount)) * 100, 2)
            : 0;

        return [
            'total_sessions' => $total,
            'completed' => $completed,
            'ongoing' => $ongoing,
            'pending' => $pending,
            'canceled' => $canceled,
            'utilization_rate' => $utilizationRate,
        ];
    }

    /**
     * Get period dates
     */
    private function getPeriodDates(string $period): array
    {
        $now = Carbon::now();

        switch ($period) {
            case '24h':
                return [
                    'start' => $now->copy()->subDay(),
                    'end' => $now,
                    'previous_start' => $now->copy()->subDays(2),
                    'previous_end' => $now->copy()->subDay(),
                ];
            case '7d':
                return [
                    'start' => $now->copy()->subDays(7),
                    'end' => $now,
                    'previous_start' => $now->copy()->subDays(14),
                    'previous_end' => $now->copy()->subDays(7),
                ];
            case '30d':
                return [
                    'start' => $now->copy()->subDays(30),
                    'end' => $now,
                    'previous_start' => $now->copy()->subDays(60),
                    'previous_end' => $now->copy()->subDays(30),
                ];
            default:
                return [
                    'start' => $now->copy()->subDays(7),
                    'end' => $now,
                    'previous_start' => $now->copy()->subDays(14),
                    'previous_end' => $now->copy()->subDays(7),
                ];
        }
    }
}
