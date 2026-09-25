<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomBlocking;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomBlockingController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RoomBlocking::class);

        $query = RoomBlocking::query();

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
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

        if ($request->has('active_only')) {
            $query->where('end_datetime', '>=', now());
        }

        $blockings = $query->with(['room', 'createdBy'])
            ->orderBy('start_datetime', 'desc')
            ->paginate($request->per_page ?? 9);

        return $this->success(['blockings' => $blockings], 'Room blockings list retrieved', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', RoomBlocking::class);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'reason' => 'required|string|max:255',
            'blocking_type' => 'required|in:maintenance,event,holiday,other',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|array',
        ]);

        $validated['created_by'] = $request->user()->id;

        // Check for conflicts with existing plannings
        $room = Room::findOrFail($validated['room_id']);
        $conflicts = $room->shiftPlannings()
            ->where('date', '>=', date('Y-m-d', strtotime($validated['start_datetime'])))
            ->where('date', '<=', date('Y-m-d', strtotime($validated['end_datetime'])))
            ->where(function ($q) use ($validated) {
                $q->whereBetween('starting_hour', [
                    date('H:i:s', strtotime($validated['start_datetime'])),
                    date('H:i:s', strtotime($validated['end_datetime'])),
                ])->orWhereBetween('ending_hour', [
                    date('H:i:s', strtotime($validated['start_datetime'])),
                    date('H:i:s', strtotime($validated['end_datetime'])),
                ]);
            })
            ->with(['teacher', 'course', 'courseClass'])
            ->get();

        $blocking = RoomBlocking::create($validated);

        $response = [
            'blocking' => $blocking->load(['room', 'createdBy']),
        ];

        if ($conflicts->count() > 0) {
            $response['warnings'] = [
                'message' => 'This blocking conflicts with existing plannings',
                'conflicts_count' => $conflicts->count(),
                'conflicts' => $conflicts,
            ];
        }

        return $this->success(
            $response,
            'Room blocking created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $blocking = RoomBlocking::with(['room', 'createdBy'])->findOrFail($id);
        $this->authorize('view', $blocking);

        return $this->success(['blocking' => $blocking], 'Room blocking retrieved', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $blocking = RoomBlocking::findOrFail($id);
        $this->authorize('update', $blocking);

        $validated = $request->validate([
            'room_id' => 'sometimes|exists:rooms,id',
            'start_datetime' => 'sometimes|date',
            'end_datetime' => 'sometimes|date|after:start_datetime',
            'reason' => 'sometimes|string|max:255',
            'blocking_type' => 'sometimes|in:maintenance,event,holiday,other',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|array',
        ]);

        $blocking->update($validated);

        return $this->success(
            ['blocking' => $blocking->load(['room', 'createdBy'])],
            'Room blocking updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $blocking = RoomBlocking::findOrFail($id);
        $this->authorize('delete', $blocking);
        $blocking->delete();

        return $this->success(null, 'Room blocking deleted successfully', 200);
    }

    /**
     * Get blockings for a specific room.
     */
    public function getByRoom(Request $request, string $roomId): JsonResponse
    {
        $room = Room::findOrFail($roomId);
        $this->authorize('view', $room);

        $query = $room->blockings();

        if ($request->has('start_date')) {
            $query->where('end_datetime', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_datetime', '<=', $request->end_date);
        }

        $blockings = $query->with('createdBy')
            ->orderBy('start_datetime')
            ->paginate($request->per_page ?? 9);

        return $this->success(
            ['blockings' => $blockings],
            'Room blockings retrieved',
            200
        );
    }

    /**
     * Check conflicts before creating a blocking.
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RoomBlocking::class);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        // Check existing blockings
        $blockingConflicts = $room->blockings()
            ->where('start_datetime', '<', $validated['end_datetime'])
            ->where('end_datetime', '>', $validated['start_datetime'])
            ->get();

        // Check plannings
        $planningConflicts = $room->shiftPlannings()
            ->where('date', '>=', date('Y-m-d', strtotime($validated['start_datetime'])))
            ->where('date', '<=', date('Y-m-d', strtotime($validated['end_datetime'])))
            ->where(function ($q) use ($validated) {
                $q->whereBetween('starting_hour', [
                    date('H:i:s', strtotime($validated['start_datetime'])),
                    date('H:i:s', strtotime($validated['end_datetime'])),
                ])->orWhereBetween('ending_hour', [
                    date('H:i:s', strtotime($validated['start_datetime'])),
                    date('H:i:s', strtotime($validated['end_datetime'])),
                ]);
            })
            ->with(['teacher', 'course', 'courseClass'])
            ->get();

        $hasConflicts = $blockingConflicts->count() > 0 || $planningConflicts->count() > 0;

        return $this->success([
            'has_conflicts' => $hasConflicts,
            'blocking_conflicts' => $blockingConflicts,
            'planning_conflicts' => $planningConflicts,
        ], 'Conflict check completed', 200);
    }
}
