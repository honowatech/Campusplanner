<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            // Amphitheater
            [
                'name' => 'Grand Amphitheater',
                'code' => 'AMP-A',
                'type' => 'amphitheater',
                'capacity' => 200,
                'floor' => 0,
                'building' => 'Main Building',
                'has_projector' => true,
                'has_computers' => false,
                'has_whiteboard' => true,
            ],

            // Classrooms - Building A
            ['name' => 'Classroom A101', 'code' => 'A-101', 'type' => 'classroom', 'capacity' => 30, 'floor' => 1, 'building' => 'Building A', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Classroom A102', 'code' => 'A-102', 'type' => 'classroom', 'capacity' => 30, 'floor' => 1, 'building' => 'Building A', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Classroom A103', 'code' => 'A-103', 'type' => 'classroom', 'capacity' => 30, 'floor' => 1, 'building' => 'Building A', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Classroom A201', 'code' => 'A-201', 'type' => 'classroom', 'capacity' => 35, 'floor' => 2, 'building' => 'Building A', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Classroom A202', 'code' => 'A-202', 'type' => 'classroom', 'capacity' => 35, 'floor' => 2, 'building' => 'Building A', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],

            // Classrooms - Building B
            ['name' => 'Classroom B101', 'code' => 'B-101', 'type' => 'classroom', 'capacity' => 40, 'floor' => 1, 'building' => 'Building B', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Classroom B102', 'code' => 'B-102', 'type' => 'classroom', 'capacity' => 40, 'floor' => 1, 'building' => 'Building B', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Classroom B201', 'code' => 'B-201', 'type' => 'classroom', 'capacity' => 45, 'floor' => 2, 'building' => 'Building B', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],

            // Labs
            ['name' => 'Computer Lab 1', 'code' => 'LAB-INFO-1', 'type' => 'lab', 'capacity' => 25, 'floor' => 1, 'building' => 'Building C', 'has_projector' => true, 'has_computers' => true, 'has_whiteboard' => true, 'dept' => 'INFO'],
            ['name' => 'Computer Lab 2', 'code' => 'LAB-INFO-2', 'type' => 'lab', 'capacity' => 25, 'floor' => 1, 'building' => 'Building C', 'has_projector' => true, 'has_computers' => true, 'has_whiteboard' => true, 'dept' => 'INFO'],
            ['name' => 'Physics Lab', 'code' => 'LAB-PHYS', 'type' => 'lab', 'capacity' => 20, 'floor' => 2, 'building' => 'Building C', 'has_projector' => true, 'has_computers' => true, 'has_whiteboard' => true, 'dept' => 'PHYS'],
            ['name' => 'Chemistry Lab', 'code' => 'LAB-CHEM', 'type' => 'lab', 'capacity' => 20, 'floor' => 2, 'building' => 'Building C', 'has_projector' => true, 'has_computers' => false, 'has_whiteboard' => true],

            // Study Rooms
            ['name' => 'Study Room 1', 'code' => 'STUDY-1', 'type' => 'study_room', 'capacity' => 10, 'floor' => 1, 'building' => 'Library', 'has_projector' => false, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Study Room 2', 'code' => 'STUDY-2', 'type' => 'study_room', 'capacity' => 10, 'floor' => 1, 'building' => 'Library', 'has_projector' => false, 'has_computers' => false, 'has_whiteboard' => true],
            ['name' => 'Study Room 3', 'code' => 'STUDY-3', 'type' => 'study_room', 'capacity' => 8, 'floor' => 2, 'building' => 'Library', 'has_projector' => false, 'has_computers' => false, 'has_whiteboard' => true],

            // Conference Rooms
            ['name' => 'Conference Room 1', 'code' => 'CONF-1', 'type' => 'conference', 'capacity' => 15, 'floor' => 1, 'building' => 'Admin Building', 'has_projector' => true, 'has_computers' => true, 'has_whiteboard' => true],
            ['name' => 'Conference Room 2', 'code' => 'CONF-2', 'type' => 'conference', 'capacity' => 20, 'floor' => 2, 'building' => 'Admin Building', 'has_projector' => true, 'has_computers' => true, 'has_whiteboard' => true],
        ];

        foreach ($rooms as $roomData) {
            $department = null;
            if (isset($roomData['dept'])) {
                $department = Department::where('code', $roomData['dept'])->first();
            }

            Room::firstOrCreate(
                ['code' => $roomData['code']],
                [
                    'name' => $roomData['name'],
                    'code' => $roomData['code'],
                    'department_id' => $department?->id,
                    'type' => $roomData['type'],
                    'capacity' => $roomData['capacity'],
                    'floor' => $roomData['floor'],
                    'building' => $roomData['building'],
                    'has_projector' => $roomData['has_projector'],
                    'has_computers' => $roomData['has_computers'],
                    'has_whiteboard' => $roomData['has_whiteboard'],
                    'is_active' => true,
                    'description' => $roomData['name'].' - '.$roomData['building'].', Floor '.$roomData['floor'],
                ]
            );
        }

        $this->command->info('Rooms seeded successfully! (20 rooms created)');
    }
}
