<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherBlocking;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherBlockingController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TeacherBlocking::class);

        $query = TeacherBlocking::query();

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('blocking_type')) {
            $query->where('blocking_type', $request->blocking_type);
        }

        if ($request->has('start_date')) {
            $query->where('end_datetime', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_datetime', '<=', $request->end_date);
        }

        if ($request->has('pending_only')) {
            $query->where('status', 'pending');
        }

        $blockings = $query->with(['teacher', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 9);

        return $this->success(['blockings' => $blockings], 'Teacher blockings list retrieved', 200);
    }

    /**
     * Store a newly created resource in storage.
     * This is when a teacher submits a blocking request.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TeacherBlocking::class);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'start_datetime' => 'required|date|after:now',
            'end_datetime' => 'required|date|after:start_datetime',
            'reason' => 'required|string|max:255',
            'blocking_type' => 'required|in:absence,vacation,training,medical,other',
        ]);

        // Check for overlapping blockings (only approved ones)
        $teacher = Teacher::findOrFail($validated['teacher_id']);
        $hasOverlapping = $teacher->blockings()
            ->where('status', 'approved')
            ->where('start_datetime', '<', $validated['end_datetime'])
            ->where('end_datetime', '>', $validated['start_datetime'])
            ->exists();

        if ($hasOverlapping) {
            return $this->error(
                null,
                'You already have an approved blocking for this period.',
                422
            );
        }

        $blocking = TeacherBlocking::create([
            ...$validated,
            'status' => 'pending',
        ]);

        return $this->success(
            ['blocking' => $blocking->load('teacher')],
            'Blocking request submitted successfully and is pending approval',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $blocking = TeacherBlocking::with(['teacher', 'approvedBy'])->findOrFail($id);
        $this->authorize('view', $blocking);

        return $this->success(['blocking' => $blocking], 'Teacher blocking retrieved', 200);
    }

    /**
     * Update the specified resource in storage.
     * Only allowed if status is pending.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $blocking = TeacherBlocking::findOrFail($id);
        $this->authorize('update', $blocking);

        // Only allow updates if still pending
        if (! $blocking->isPending()) {
            return $this->error(
                null,
                'Cannot update a blocking that has already been '.$blocking->status,
                403
            );
        }

        $validated = $request->validate([
            'start_datetime' => 'sometimes|date|after:now',
            'end_datetime' => 'sometimes|date|after:start_datetime',
            'reason' => 'sometimes|string|max:255',
            'blocking_type' => 'sometimes|in:absence,vacation,training,medical,other',
        ]);

        $blocking->update($validated);

        return $this->success(
            ['blocking' => $blocking->load('teacher')],
            'Blocking request updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     * Only allowed if status is pending.
     */
    public function destroy(string $id): JsonResponse
    {
        $blocking = TeacherBlocking::findOrFail($id);
        $this->authorize('delete', $blocking);

        // Only allow deletion if still pending
        if (! $blocking->isPending()) {
            return $this->error(
                null,
                'Cannot delete a blocking that has already been '.$blocking->status,
                403
            );
        }

        $blocking->delete();

        return $this->success(null, 'Blocking request deleted successfully', 200);
    }

    /**
     * Approve a blocking request.
     * Only accessible by admins.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $blocking = TeacherBlocking::findOrFail($id);
        $this->authorize('approve', $blocking);

        if (! $blocking->isPending()) {
            return $this->error(
                null,
                'This blocking has already been '.$blocking->status,
                422
            );
        }

        $blocking->approve($request->user()->id);

        return $this->success(
            ['blocking' => $blocking->load(['teacher', 'approvedBy'])],
            'Blocking request approved successfully',
            200
        );
    }

    /**
     * Reject a blocking request.
     * Only accessible by admins.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $blocking = TeacherBlocking::findOrFail($id);
        $this->authorize('reject', $blocking);

        if (! $blocking->isPending()) {
            return $this->error(
                null,
                'This blocking has already been '.$blocking->status,
                422
            );
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $blocking->reject($request->user()->id, $validated['rejection_reason'] ?? null);

        return $this->success(
            ['blocking' => $blocking->load(['teacher', 'approvedBy'])],
            'Blocking request rejected',
            200
        );
    }

    /**
     * Get all pending blockings (for admin dashboard).
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TeacherBlocking::class);

        $blockings = TeacherBlocking::pending()
            ->with('teacher')
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 9);

        return $this->success(
            ['blockings' => $blockings],
            'Pending blockings retrieved',
            200
        );
    }

    /**
     * Get blockings for a specific teacher.
     */
    public function getByTeacher(Request $request, string $teacherId): JsonResponse
    {
        $teacher = Teacher::findOrFail($teacherId);
        $this->authorize('view', $teacher);

        $query = $teacher->blockings();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->where('end_datetime', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_datetime', '<=', $request->end_date);
        }

        $blockings = $query->with('approvedBy')
            ->orderBy('start_datetime', 'desc')
            ->paginate($request->per_page ?? 9);

        return $this->success(
            ['blockings' => $blockings],
            'Teacher blockings retrieved',
            200
        );
    }

    /**
     * Get my blockings (for authenticated teacher).
     */
    public function myBlockings(Request $request): JsonResponse
    {
        $this->authorize('my', TeacherBlocking::class);
        $user = $request->user();

        // Find teacher associated with this user
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            return $this->error(null, 'No teacher profile found for this user', 404);
        }

        $query = $teacher->blockings();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $blockings = $query->with('approvedBy')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 9);

        return $this->success(
            ['blockings' => $blockings],
            'My blockings retrieved',
            200
        );
    }

    /**
     * Check if a teacher is available at a given time.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TeacherBlocking::class);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
        ]);

        $teacher = Teacher::findOrFail($validated['teacher_id']);

        // Check approved blockings
        $hasBlocking = $teacher->blockings()
            ->where('status', 'approved')
            ->where('start_datetime', '<', $validated['end_datetime'])
            ->where('end_datetime', '>', $validated['start_datetime'])
            ->exists();

        // Check plannings
        $hasPlanning = $teacher->shiftPlannings()
            ->where('date', date('Y-m-d', strtotime($validated['start_datetime'])))
            ->where(function ($q) use ($validated) {
                $q->whereBetween('starting_hour', [
                    date('H:i:s', strtotime($validated['start_datetime'])),
                    date('H:i:s', strtotime($validated['end_datetime'])),
                ])->orWhereBetween('ending_hour', [
                    date('H:i:s', strtotime($validated['start_datetime'])),
                    date('H:i:s', strtotime($validated['end_datetime'])),
                ]);
            })
            ->exists();

        $isAvailable = ! $hasBlocking && ! $hasPlanning;

        // Get conflicting events if not available
        $conflicts = [];
        if (! $isAvailable) {
            $conflicts['blockings'] = $teacher->blockings()
                ->where('status', 'approved')
                ->where('start_datetime', '<', $validated['end_datetime'])
                ->where('end_datetime', '>', $validated['start_datetime'])
                ->get();

            $conflicts['plannings'] = $teacher->shiftPlannings()
                ->where('date', date('Y-m-d', strtotime($validated['start_datetime'])))
                ->where(function ($q) use ($validated) {
                    $q->whereBetween('starting_hour', [
                        date('H:i:s', strtotime($validated['start_datetime'])),
                        date('H:i:s', strtotime($validated['end_datetime'])),
                    ])->orWhereBetween('ending_hour', [
                        date('H:i:s', strtotime($validated['start_datetime'])),
                        date('H:i:s', strtotime($validated['end_datetime'])),
                    ]);
                })
                ->with(['class', 'subject'])
                ->get();
        }

        return $this->success([
            'is_available' => $isAvailable,
            'conflicts' => $conflicts,
        ], 'Teacher availability checked', 200);
    }
}
