<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Services\ResourceAvailabilityService;
use App\Traits\AppliesDataScope;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use AppliesDataScope;
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
        $this->authorize('viewAny', Room::class);

        $query = Room::query();

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

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        if ($request->has('has_projector')) {
            $query->where('has_projector', $request->boolean('has_projector'));
        }

        if ($request->has('has_computers')) {
            $query->where('has_computers', $request->boolean('has_computers'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rooms = $query->with(['department'])
            ->paginate($request->per_page ?? 9);

        return $this->success(['rooms' => $rooms], 'Rooms list retrieved', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $this->authorize('create', Room::class);

        $validated = $request->validated();

        $room = Room::create($validated);

        return $this->success(
            ['room' => $room->load('department')],
            'Room created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $room = Room::with([
            'department',
            'blockings' => function ($query) {
                $query->where('end_datetime', '>=', now())
                    ->orderBy('start_datetime');
            },
        ])->findOrFail($id);
        $this->authorize('view', $room);

        return $this->success(['room' => $room], 'Room retrieved', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $this->authorize('update', $room);

        $validated = $request->validated();

        $room->update($validated);

        return $this->success(
            ['room' => $room->load('department')],
            'Room updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $this->authorize('delete', $room);

        // Check if room has plannings
        if ($room->shiftPlannings()->count() > 0) {
            return $this->error(
                null,
                'Cannot delete room with existing plannings. Please remove plannings first.',
                400
            );
        }

        // Check if room has classes assigned
        if ($room->classes()->count() > 0) {
            return $this->error(
                null,
                'Cannot delete room assigned to classes. Please reassign classes first.',
                400
            );
        }

        $room->delete();

        return $this->success(null, 'Room deleted successfully', 200);
    }

    /**
     * Get room blockings.
     */
    public function getBlockings(string $id, Request $request): JsonResponse
    {
        $room = Room::findOrFail($id);
        $this->authorize('view', $room);

        $query = $room->blockings();

        if ($request->has('start_date')) {
            $query->where('end_datetime', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_datetime', '<=', $request->end_date);
        }

        $blockings = $query->orderBy('start_datetime')->paginate(15);

        return $this->success(
            ['blockings' => $blockings],
            'Room blockings retrieved',
            200
        );
    }

    /**
     * Check room availability.
     */
    public function checkAvailability(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $this->authorize('view', $room);

        $validated = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
        ]);

        $isAvailable = $room->isAvailable(
            $validated['start_datetime'],
            $validated['end_datetime']
        );

        // Get conflicting events if not available
        $conflicts = [];
        if (! $isAvailable) {
            $conflicts['blockings'] = $room->blockings()
                ->where('start_datetime', '<', $validated['end_datetime'])
                ->where('end_datetime', '>', $validated['start_datetime'])
                ->get();

            $conflicts['plannings'] = $room->shiftPlannings()
                ->where('date', date('Y-m-d', strtotime($validated['start_datetime'])))
                ->where('starting_hour', '<', date('H:i:s', strtotime($validated['end_datetime'])))
                ->where('ending_hour', '>', date('H:i:s', strtotime($validated['start_datetime'])))
                ->with(['teacher', 'course', 'courseClass'])
                ->get();
        }

        return $this->success([
            'is_available' => $isAvailable,
            'conflicts' => $conflicts,
        ], 'Room availability checked', 200);
    }

    /**
     * Get room schedule.
     */
    public function getSchedule(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $this->authorize('view', $room);

        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', date('Y-m-d', strtotime($startDate.' + 30 days')));

        $plannings = $room->shiftPlannings()
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['teacher', 'course', 'courseClass'])
            ->orderBy('date')
            ->orderBy('starting_hour')
            ->get();

        $blockings = $room->blockings()
            ->where('end_datetime', '>=', $startDate)
            ->where('start_datetime', '<=', $endDate)
            ->orderBy('start_datetime')
            ->get();

        return $this->success([
            'plannings' => $plannings,
            'blockings' => $blockings,
        ], 'Room schedule retrieved', 200);
    }

    /**
     * Search for available rooms at a given time slot.
     */
    public function searchAvailable(Request $request): JsonResponse
    {
        $this->authorize('search', Room::class);

        $validated = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'department_id' => 'nullable|exists:departments,id',
            'type' => 'nullable|in:classroom,lab,amphitheater,conference,study_room',
            'min_capacity' => 'nullable|integer|min:1',
            'has_projector' => 'nullable|boolean',
            'has_computers' => 'nullable|boolean',
            'has_whiteboard' => 'nullable|boolean',
        ]);

        $filters = [
            'department_id' => $validated['department_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'min_capacity' => $validated['min_capacity'] ?? null,
            'has_projector' => $request->boolean('has_projector'),
            'has_computers' => $request->boolean('has_computers'),
            'has_whiteboard' => $request->boolean('has_whiteboard'),
        ];

        // Restreint la recherche au département de l'utilisateur pour les rôles
        // à périmètre département (super-admin / administrateur voient tout).
        $departmentScope = $this->departmentScope($request->user());
        if ($departmentScope !== null) {
            $filters['department_id'] = $departmentScope;
        }

        $rooms = $this->availabilityService->findAvailableRooms(
            $validated['start_datetime'],
            $validated['end_datetime'],
            array_filter($filters),
            $request->per_page ?? 15
        );

        return $this->success(
            ['rooms' => $rooms],
            'Salles disponibles trouvées',
            200
        );
    }
}
