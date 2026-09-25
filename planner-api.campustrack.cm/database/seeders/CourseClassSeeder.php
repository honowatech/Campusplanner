<?php

namespace Database\Seeders;

use App\Models\CourseClass;
use App\Models\Department;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseClassSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roomAssignments = [
            'INFO-B1' => 'A-101',
            'INFO-B2' => 'A-102',
            'INFO-M1' => 'A-201',
            'MATH-B1' => 'A-103',
            'MATH-B2' => 'A-202',
            'PHYS-B1' => 'B-101',
            'PHYS-B2' => 'B-102',
            'ECO-B1' => 'B-201',
            'ECO-B2' => 'AMP-A',
        ];

        $classes = [
            ['name' => 'Bachelor Info 1A', 'code' => 'INFO-B1', 'dept' => 'INFO', 'level' => '1st Year', 'capacity' => 30],
            ['name' => 'Bachelor Info 2A', 'code' => 'INFO-B2', 'dept' => 'INFO', 'level' => '2nd Year', 'capacity' => 28],
            ['name' => 'Master Info 1A', 'code' => 'INFO-M1', 'dept' => 'INFO', 'level' => 'Master 1', 'capacity' => 25],
            ['name' => 'Bachelor Math 1A', 'code' => 'MATH-B1', 'dept' => 'MATH', 'level' => '1st Year', 'capacity' => 35],
            ['name' => 'Bachelor Math 2A', 'code' => 'MATH-B2', 'dept' => 'MATH', 'level' => '2nd Year', 'capacity' => 32],
            ['name' => 'Bachelor Physics 1A', 'code' => 'PHYS-B1', 'dept' => 'PHYS', 'level' => '1st Year', 'capacity' => 30],
            ['name' => 'Bachelor Physics 2A', 'code' => 'PHYS-B2', 'dept' => 'PHYS', 'level' => '2nd Year', 'capacity' => 28],
            ['name' => 'Bachelor Economics 1A', 'code' => 'ECO-B1', 'dept' => 'ECO', 'level' => '1st Year', 'capacity' => 40],
            ['name' => 'Bachelor Economics 2A', 'code' => 'ECO-B2', 'dept' => 'ECO', 'level' => '2nd Year', 'capacity' => 38],
        ];

        $academicYear = '2024-2025';

        foreach ($classes as $classData) {
            $department = Department::where('code', $classData['dept'])->first();
            $room = Room::where('code', $roomAssignments[$classData['code']])->first();

            CourseClass::firstOrCreate(
                ['code' => $classData['code']],
                [
                    'name' => $classData['name'],
                    'code' => $classData['code'],
                    'department_id' => $department?->id,
                    'room_id' => $room?->id,
                    'level' => $classData['level'],
                    'capacity' => $classData['capacity'],
                    'academic_year' => $academicYear,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Course classes seeded successfully! (9 classes created with room assignments)');
    }
}
