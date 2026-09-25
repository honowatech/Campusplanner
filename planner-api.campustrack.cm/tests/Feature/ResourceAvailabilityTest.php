<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Room;
use App\Models\RoomBlocking;
use App\Models\Teacher;
use App\Models\TeacherBlocking;
use App\Models\User;
use App\Services\ResourceAvailabilityService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResourceAvailabilityTest extends TestCase
{
    private ResourceAvailabilityService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResourceAvailabilityService::class);
        $this->user = User::factory()->create();

        // L'utilisateur de test a les permissions requises par les policies
        foreach (['rooms.view', 'rooms.search', 'teachers.view.all'] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
        $this->user->givePermissionTo(['rooms.view', 'rooms.search', 'teachers.view.all']);
    }

    /** @test */
    public function it_can_find_available_rooms(): void
    {
        $department = Department::factory()->create();

        // Create available rooms
        $room1 = Room::factory()->create([
            'department_id' => $department->id,
            'capacity' => 30,
            'type' => 'classroom',
            'is_active' => true,
        ]);

        $room2 = Room::factory()->create([
            'department_id' => $department->id,
            'capacity' => 50,
            'type' => 'lab',
            'is_active' => true,
        ]);

        // Create a blocked room
        $room3 = Room::factory()->create([
            'department_id' => $department->id,
            'capacity' => 40,
            'type' => 'classroom',
            'is_active' => true,
        ]);

        // Block room3
        RoomBlocking::create([
            'room_id' => $room3->id,
            'start_datetime' => now()->addDay()->setTime(9, 0),
            'end_datetime' => now()->addDay()->setTime(11, 0),
            'reason' => 'Maintenance',
            'blocking_type' => 'maintenance',
            'created_by' => $this->user->id,
        ]);

        // Search for available rooms
        $start = now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s');
        $end = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        $availableRooms = $this->service->findAvailableRooms($start, $end);

        $this->assertEquals(2, $availableRooms->count());
        $this->assertTrue($availableRooms->pluck('id')->contains($room1->id));
        $this->assertTrue($availableRooms->pluck('id')->contains($room2->id));
        $this->assertFalse($availableRooms->pluck('id')->contains($room3->id));
    }

    /** @test */
    public function it_can_find_available_rooms_with_filters(): void
    {
        $department = Department::factory()->create();

        $room1 = Room::factory()->create([
            'department_id' => $department->id,
            'capacity' => 30,
            'type' => 'classroom',
            'has_projector' => true,
            'has_computers' => false,
            'is_active' => true,
        ]);

        $room2 = Room::factory()->create([
            'department_id' => $department->id,
            'capacity' => 50,
            'type' => 'lab',
            'has_projector' => true,
            'has_computers' => true,
            'is_active' => true,
        ]);

        $start = now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s');
        $end = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        // Filter by min capacity
        $rooms = $this->service->findAvailableRooms($start, $end, ['min_capacity' => 40]);
        $this->assertEquals(1, $rooms->count());
        $this->assertEquals($room2->id, $rooms->first()->id);

        // Filter by type
        $rooms = $this->service->findAvailableRooms($start, $end, ['type' => 'lab']);
        $this->assertEquals(1, $rooms->count());
        $this->assertEquals($room2->id, $rooms->first()->id);

        // Filter by has_computers
        $rooms = $this->service->findAvailableRooms($start, $end, ['has_computers' => true]);
        $this->assertEquals(1, $rooms->count());
        $this->assertEquals($room2->id, $rooms->first()->id);
    }

    /** @test */
    public function it_can_find_available_teachers(): void
    {
        $department = Department::factory()->create();
        $course = Course::factory()->create();

        // Create available teachers
        $teacher1 = Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        $teacher1->courses()->attach($course);

        $teacher2 = Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        // Create a blocked teacher
        $teacher3 = Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        // Block teacher3
        TeacherBlocking::create([
            'teacher_id' => $teacher3->id,
            'start_datetime' => now()->addDay()->setTime(9, 0),
            'end_datetime' => now()->addDay()->setTime(11, 0),
            'reason' => 'Medical',
            'blocking_type' => 'medical',
            'status' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $start = now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s');
        $end = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        $availableTeachers = $this->service->findAvailableTeachers($start, $end);

        $this->assertEquals(2, $availableTeachers->count());
        $this->assertTrue($availableTeachers->pluck('id')->contains($teacher1->id));
        $this->assertTrue($availableTeachers->pluck('id')->contains($teacher2->id));
        $this->assertFalse($availableTeachers->pluck('id')->contains($teacher3->id));
    }

    /** @test */
    public function it_can_find_available_teachers_with_course_filter(): void
    {
        $department = Department::factory()->create();
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        $teacher1 = Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        $teacher1->courses()->attach($course1);

        $teacher2 = Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        $teacher2->courses()->attach($course2);

        $start = now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s');
        $end = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        // Filter by course
        $teachers = $this->service->findAvailableTeachers($start, $end, [
            'course_id' => $course1->id,
        ]);

        $this->assertEquals(1, $teachers->count());
        $this->assertEquals($teacher1->id, $teachers->first()->id);
    }

    /** @test */
    public function pending_teacher_blockings_do_not_affect_availability(): void
    {
        $department = Department::factory()->create();

        $teacher = Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        // Create a pending blocking (should not affect availability)
        TeacherBlocking::create([
            'teacher_id' => $teacher->id,
            'start_datetime' => now()->addDay()->setTime(9, 0),
            'end_datetime' => now()->addDay()->setTime(11, 0),
            'reason' => 'Vacation',
            'blocking_type' => 'vacation',
            'status' => 'pending',
        ]);

        $start = now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s');
        $end = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        $availableTeachers = $this->service->findAvailableTeachers($start, $end);

        $this->assertEquals(1, $availableTeachers->count());
        $this->assertTrue($availableTeachers->pluck('id')->contains($teacher->id));
    }

    /** @test */
    public function api_can_search_available_rooms(): void
    {
        $department = Department::factory()->create();

        Room::factory()->create([
            'department_id' => $department->id,
            'capacity' => 30,
            'type' => 'classroom',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/rooms/search-available', [
            'start_datetime' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'type' => 'classroom',
            'min_capacity' => 20,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'rooms' => [
                        'data',
                        'current_page',
                        'total',
                    ],
                ],
            ]);
    }

    /** @test */
    public function api_can_search_available_teachers(): void
    {
        $department = Department::factory()->create();

        Teacher::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/teachers/search-available', [
            'start_datetime' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'department_id' => $department->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'teachers' => [
                        'data',
                        'current_page',
                        'total',
                    ],
                ],
            ]);
    }

    /** @test */
    public function api_validates_required_fields_for_room_search(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/rooms/search-available', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_datetime', 'end_datetime']);
    }

    /** @test */
    public function api_validates_required_fields_for_teacher_search(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/teachers/search-available', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_datetime', 'end_datetime']);
    }
}
