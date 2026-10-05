<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Student;
use App\Traits\AppliesDataScope;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseClassController extends Controller
{
    use AppliesDataScope;
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CourseClass::class);

        $query = $this->scopeClasses(CourseClass::query(), $request->user());

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $classes = $query->with(['department', 'students'])
            ->withCount('students')
            ->paginate($request->per_page ?? 9);

        return $this->success(['classes' => $classes], 'Classes list retrieved', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CourseClass::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:classes,code',
            'department_id' => 'required|exists:departments,id',
            'level' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'academic_year' => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        $class = CourseClass::create($validated);

        return $this->success(
            ['class' => $class->load('department')],
            'Class created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $class = CourseClass::with([
            'department',
            'students' => function ($query) {
                $query->with('user')->orderBy('last_name');
            },
            'shiftPlannings' => function ($query) {
                $query->where('date', '>=', now()->format('Y-m-d'))
                    ->orderBy('date')
                    ->orderBy('starting_hour')
                    ->with(['teacher', 'course', 'room']);
            },
        ])->withCount('students')->findOrFail($id);
        $this->authorize('view', $class);

        return $this->success(['class' => $class], 'Class retrieved', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $class = CourseClass::findOrFail($id);
        $this->authorize('update', $class);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:classes,code,'.$id,
            'department_id' => 'sometimes|exists:departments,id',
            'level' => 'sometimes|string|max:50',
            'capacity' => 'sometimes|integer|min:1',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'academic_year' => 'sometimes|string|max:20',
            'is_active' => 'boolean',
        ]);

        // Check if reducing capacity would cause overflow
        if (isset($validated['capacity']) && $validated['capacity'] < $class->students()->count()) {
            return $this->error(
                null,
                'Cannot reduce capacity below current student count ('.$class->students()->count().')',
                422
            );
        }

        $class->update($validated);

        return $this->success(
            ['class' => $class->load('department')],
            'Class updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $class = CourseClass::findOrFail($id);
        $this->authorize('delete', $class);

        // Check if class has students
        if ($class->students()->count() > 0) {
            return $this->error(
                null,
                'Cannot delete class with enrolled students. Please reassign students first.',
                400
            );
        }

        // Check if class has plannings
        if ($class->shiftPlannings()->count() > 0) {
            return $this->error(
                null,
                'Cannot delete class with existing plannings. Please remove plannings first.',
                400
            );
        }

        $class->delete();

        return $this->success(null, 'Class deleted successfully', 200);
    }

    /**
     * Get students in this class.
     */
    public function getStudents(string $id): JsonResponse
    {
        $class = CourseClass::findOrFail($id);
        $this->authorize('view', $class);
        $students = $class->students()->with('user')->paginate(15);

        return $this->success(
            [
                'students' => $students,
                'total_capacity' => $class->capacity,
                'enrolled_count' => $class->students()->count(),
                'available_spots' => $class->getAvailableSpots(),
            ],
            'Class students retrieved',
            200
        );
    }

    /**
     * Add students to class.
     */
    public function addStudents(Request $request, string $id): JsonResponse
    {
        $class = CourseClass::findOrFail($id);
        $this->authorize('update', $class);

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $availableSpots = $class->getAvailableSpots();
        $studentCount = count($validated['student_ids']);

        if ($studentCount > $availableSpots) {
            return $this->error(
                null,
                "Not enough spots available. Only {$availableSpots} spots left.",
                422
            );
        }

        // Update students' course_class_id
        Student::whereIn('id', $validated['student_ids'])->update(['course_class_id' => $class->id]);

        return $this->success(
            [
                'added_count' => $studentCount,
                'available_spots' => $class->getAvailableSpots(),
            ],
            'Students added to class successfully',
            200
        );
    }

    /**
     * Get class schedule.
     */
    public function getSchedule(Request $request, string $id): JsonResponse
    {
        $class = CourseClass::findOrFail($id);
        $this->authorize('view', $class);

        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', date('Y-m-d', strtotime($startDate.' + 30 days')));

        $plannings = $class->shiftPlannings()
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['teacher', 'course', 'room'])
            ->orderBy('date')
            ->orderBy('starting_hour')
            ->get();

        return $this->success(
            ['schedule' => $plannings],
            'Class schedule retrieved',
            200
        );
    }

    /**
     * Get teachers assigned to this class.
     */
    public function getTeachers(string $id): JsonResponse
    {
        $class = CourseClass::findOrFail($id);
        $this->authorize('view', $class);

        $teachers = $class->teachers()
            ->distinct()
            ->with('user')
            ->get();

        return $this->success(
            ['teachers' => $teachers],
            'Class teachers retrieved',
            200
        );
    }
}
