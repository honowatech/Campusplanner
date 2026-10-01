<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\CourseClass;
use App\Models\Student;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($request->has('course_class_id')) {
            $query->where('course_class_id', $request->course_class_id);
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $students = $query->with(['user', 'class'])
            ->paginate($request->per_page ?? 9);

        return $this->success(['students' => $students], 'Students list retrieved', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);

        $validated = $request->validated();

        // Création atomique : compte utilisateur + rôle + fiche étudiant
        $student = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->assignRole('etudiant');

            $studentData = $validated;
            $studentData['user_id'] = $user->id;
            unset($studentData['password']);

            return Student::create($studentData);
        });

        return $this->success(
            ['student' => $student->load(['user', 'class'])],
            'Student created successfully with user account',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $student = Student::with([
            'user',
            'class.department',
            'class.shiftPlannings' => function ($query) {
                $query->where('date', '>=', now()->format('Y-m-d'))
                    ->orderBy('date')
                    ->orderBy('starting_hour')
                    ->with(['teacher', 'course']);
            },
        ])->findOrFail($id);
        $this->authorize('view', $student);

        return $this->success(['student' => $student], 'Student retrieved', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $this->authorize('update', $student);

        $validated = $request->validated();

        $student->update($validated);

        // Update user name if first or last name changed
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $student->user->update([
                'name' => $student->full_name,
            ]);
        }

        // Update user email if email changed
        if (isset($validated['email'])) {
            $student->user->update([
                'email' => $validated['email'],
            ]);
        }

        return $this->success(
            ['student' => $student->load(['user', 'class'])],
            'Student updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $this->authorize('delete', $student);
        $user = $student->user;

        $student->delete();

        // Also delete the associated user account
        if ($user) {
            $user->delete();
        }

        return $this->success(null, 'Student and associated user account deleted successfully', 200);
    }

    /**
     * Get student's schedule.
     */
    public function getSchedule(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $this->authorize('view', $student);

        if (! $student->class) {
            return $this->error(null, 'Student is not assigned to any class', 404);
        }

        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', date('Y-m-d', strtotime($startDate.' + 30 days')));

        $plannings = $student->class->shiftPlannings()
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['teacher', 'course', 'room'])
            ->orderBy('date')
            ->orderBy('starting_hour')
            ->get();

        return $this->success(
            ['schedule' => $plannings],
            'Student schedule retrieved',
            200
        );
    }

    /**
     * Move student to another class.
     */
    public function moveToClass(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $this->authorize('update', $student);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $newClass = CourseClass::findOrFail($validated['class_id']);

        // Check if new class has available spots
        if (! $newClass->hasAvailableSpace()) {
            return $this->error(
                null,
                'Target class is full. No available spots.',
                422
            );
        }

        $oldClass = $student->class;
        $student->update(['course_class_id' => $validated['class_id']]);

        return $this->success(
            [
                'student' => $student->load('class'),
                'previous_class' => $oldClass,
            ],
            'Student moved to new class successfully',
            200
        );
    }

    /**
     * Get students without a class.
     */
    public function getUnassigned(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::whereNull('course_class_id')
            ->with('user')
            ->paginate($request->per_page ?? 9);

        return $this->success(
            ['students' => $students],
            'Unassigned students retrieved',
            200
        );
    }
}
