<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Traits\AppliesDataScope;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use AppliesDataScope;
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);
        $query = $this->scopeCourses(Course::query(), $request->user());

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $courses = $query->with(['department', 'teachers'])
            ->paginate($request->per_page ?? 9);

        return $this->success(['courses' => $courses], __('resources.courses.list'), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Course::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'coefficient' => 'nullable|numeric|min:0|max:99.9',
            'hours_per_week' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Borne le département au périmètre de l'utilisateur (responsable).
        $departmentScope = $this->departmentScope($request->user());
        if ($departmentScope !== null) {
            $validated['department_id'] = $departmentScope;
        }

        $course = Course::create($validated);

        return $this->success(
            ['course' => $course->load('department')],
            __('resources.courses.created'),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $course = Course::with(['department', 'teachers', 'shiftPlannings'])->findOrFail($id);
        $this->authorize('view', $course);

        return $this->success(['course' => $course], __('resources.courses.retrieved'), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $this->authorize('update', $course);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:courses,code,'.$id,
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'coefficient' => 'nullable|numeric|min:0|max:99.9',
            'hours_per_week' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Borne le département au périmètre de l'utilisateur (responsable).
        $departmentScope = $this->departmentScope($request->user());
        if ($departmentScope !== null) {
            $validated['department_id'] = $departmentScope;
        }

        $course->update($validated);

        return $this->success(
            ['course' => $course->load('department')],
            __('resources.courses.updated'),
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $this->authorize('delete', $course);

        // Check if course has teachers
        if ($course->teachers()->count() > 0) {
            return $this->error(
                null,
                __('resources.courses.cannot_delete_with_teachers'),
                400
            );
        }

        $course->delete();

        return $this->success(null, __('resources.courses.deleted'), 200);
    }

    /**
     * Get teachers for this course.
     */
    public function getTeachers(string $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $this->authorize('view', $course);
        $teachers = $course->teachers()->paginate(15);

        return $this->success(
            ['teachers' => $teachers],
            __('resources.courses.teachers_retrieved'),
            200
        );
    }
}
