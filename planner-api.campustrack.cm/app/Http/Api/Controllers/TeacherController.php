<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Services\ResourceAvailabilityService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    use HttpResponses;

    private ResourceAvailabilityService $availabilityService;

    public function __construct(ResourceAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Teacher::class);

        $query = Teacher::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('speciality', 'like', "%{$search}%");
            });
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('course_id')) {
            $query->whereHas('courses', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $teachers = $query->with(['department', 'courses', 'user'])
            ->paginate($request->per_page ?? 9);

        return $this->success(['teachers' => $teachers], 'Liste des enseignants récupérée', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $this->authorize('create', Teacher::class);

        $validated = $request->validated();

        $courseIds = $validated['course_ids'] ?? [];
        unset($validated['course_ids']);
        $validated['hired_at'] = now();

        $teacher = DB::transaction(function () use ($validated, $courseIds) {
            $teacher = Teacher::create($validated);

            if (! empty($courseIds)) {
                $teacher->courses()->attach($courseIds);
            }

            return $teacher;
        });

        return $this->success(
            ['teacher' => $teacher->load(['department', 'courses'])],
            'Enseignant créé avec succès',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $teacher = Teacher::with([
            'department',
            'courses',
            'user',
            'shiftPlannings' => function ($q) {
                $q->where('date', '>=', now()->format('Y-m-d'))
                    ->orderBy('date')
                    ->orderBy('starting_hour');
            },
        ])->findOrFail($id);
        $this->authorize('view', $teacher);

        return $this->success(['teacher' => $teacher], 'Enseignant récupéré', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeacherRequest $request, string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $this->authorize('update', $teacher);

        $validated = $request->validated();

        $teacher->update($validated);

        return $this->success(
            ['teacher' => $teacher->load(['department', 'courses'])],
            'Enseignant mis à jour avec succès',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $this->authorize('delete', $teacher);

        // Check if teacher has plannings
        if ($teacher->shiftPlannings()->count() > 0) {
            return $this->error(
                null,
                'Cet enseignant a des plannings. Veuillez d\'abord les réassigner.',
                400
            );
        }

        $teacher->courses()->detach();
        $teacher->delete();

        return $this->success(null, 'Enseignant supprimé avec succès', 200);
    }

    /**
     * Sync courses for this teacher.
     */
    public function syncCourses(Request $request, string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $this->authorize('update', $teacher);

        $validated = $request->validate([
            'course_ids' => 'required|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $teacher->courses()->sync($validated['course_ids']);

        return $this->success(
            ['courses' => $teacher->courses],
            'Matières synchronisées avec succès',
            200
        );
    }

    /**
     * Get teacher's schedule.
     */
    public function getSchedule(Request $request, string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $this->authorize('view', $teacher);

        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', date('Y-m-d', strtotime($startDate.' + 30 days')));

        $plannings = $teacher->shiftPlannings()
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['courseClass', 'course', 'room'])
            ->orderBy('date')
            ->orderBy('starting_hour')
            ->get();

        return $this->success(
            ['schedule' => $plannings],
            'Emploi du temps récupéré',
            200
        );
    }

    /**
     * Check teacher availability.
     */
    public function checkAvailability(Request $request, string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $this->authorize('view', $teacher);

        $validated = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
        ]);

        $hasBlocking = $teacher->blockings()
            ->where('status', 'approved')
            ->where('start_datetime', '<', $validated['end_datetime'])
            ->where('end_datetime', '>', $validated['start_datetime'])
            ->exists();

        $hasPlanning = $teacher->shiftPlannings()
            ->where('date', date('Y-m-d', strtotime($validated['start_datetime'])))
            ->where('starting_hour', '<', date('H:i:s', strtotime($validated['end_datetime'])))
            ->where('ending_hour', '>', date('H:i:s', strtotime($validated['start_datetime'])))
            ->exists();

        $isAvailable = ! $hasBlocking && ! $hasPlanning;

        return $this->success([
            'is_available' => $isAvailable,
            'has_blocking' => $hasBlocking,
            'has_planning' => $hasPlanning,
        ], 'Disponibilité vérifiée', 200);
    }

    /**
     * Search for available teachers at a given time slot.
     */
    public function searchAvailable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Teacher::class);

        $validated = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'department_id' => 'nullable|exists:departments,id',
            'course_id' => 'nullable|exists:courses,id',
            'speciality' => 'nullable|string',
            'check_hours' => 'nullable|boolean',
        ]);

        $filters = [
            'department_id' => $validated['department_id'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'speciality' => $validated['speciality'] ?? null,
            'check_hours' => $request->boolean('check_hours'),
        ];

        $teachers = $this->availabilityService->findAvailableTeachers(
            $validated['start_datetime'],
            $validated['end_datetime'],
            array_filter($filters),
            $request->per_page ?? 15
        );

        return $this->success(
            ['teachers' => $teachers],
            'Enseignants disponibles trouvés',
            200
        );
    }
}
