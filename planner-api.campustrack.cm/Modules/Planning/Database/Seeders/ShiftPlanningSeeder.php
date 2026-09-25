<?php

namespace Modules\Planning\Database\Seeders;

use App\Models\CourseClass;
use App\Models\Room;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftPlanningSeeder extends Seeder
{
    use WithoutModelEvents;

    private array $timeSlots = [
        ['08:00:00', '09:00:00'],
        ['09:00:00', '10:00:00'],
        ['10:00:00', '11:00:00'],
        ['11:00:00', '12:00:00'],
        ['13:00:00', '14:00:00'],
        ['14:00:00', '15:00:00'],
        ['15:00:00', '16:00:00'],
        ['16:00:00', '17:00:00'],
        ['08:00:00', '10:00:00'],
        ['09:00:00', '11:00:00'],
        ['13:00:00', '15:00:00'],
        ['14:00:00', '16:00:00'],
        ['14:00:00', '17:00:00'],
    ];

    private array $statuses = ['pending', 'ongoing', 'completed', 'canceled'];

    public function run(): void
    {
        $planning = DB::table('planning_plannings')->first();

        if (! $planning) {
            $this->command->warn('No planning found. Please run PlanningSeeder first.');

            return;
        }

        $teachers = Teacher::with('courses')->where('is_active', true)->get();
        $rooms = Room::where('is_active', true)->get();
        $classes = CourseClass::where('is_active', true)->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No active teachers found. Please run TeacherSeeder first.');

            return;
        }

        if ($rooms->isEmpty()) {
            $this->command->warn('No active rooms found. Please run RoomSeeder first.');

            return;
        }

        if ($classes->isEmpty()) {
            $this->command->warn('No active classes found. Please run CourseClassSeeder first.');

            return;
        }

        $shiftPlannings = [];
        $startDate = Carbon::now()->startOfWeek()->addWeek();
        $totalCreated = 0;

        for ($week = 0; $week < 2; $week++) {
            for ($day = 0; $day < 6; $day++) {
                $currentDate = $startDate->copy()->addWeeks($week)->addDays($day);

                if ($currentDate->isSunday()) {
                    continue;
                }

                $sessionsPerDay = rand(3, 5);
                $usedSlots = [];

                for ($session = 0; $session < $sessionsPerDay; $session++) {
                    $availableSlots = array_diff(array_keys($this->timeSlots), $usedSlots);
                    if (empty($availableSlots)) {
                        break;
                    }

                    $slotIndex = $availableSlots[array_rand($availableSlots)];
                    $usedSlots[] = $slotIndex;
                    $timeSlot = $this->timeSlots[$slotIndex];

                    $startHour = intval(explode(':', $timeSlot[0])[0]);
                    $endHour = intval(explode(':', $timeSlot[1])[0]);

                    if ($startHour < 12 && $endHour > 12) {
                        continue;
                    }

                    $teacher = $this->getRandomTeacherWithCourse($teachers);
                    if (! $teacher) {
                        continue;
                    }

                    $course = $teacher->courses->random();
                    $class = $classes->random();

                    $suitableRooms = $rooms->filter(function ($room) use ($class) {
                        return $room->capacity >= ($class->capacity ?? 30) * 0.7;
                    });
                    $room = $suitableRooms->isNotEmpty() ? $suitableRooms->random() : $rooms->random();

                    $status = $this->determineStatus($currentDate, $timeSlot[0]);
                    $notes = $this->generateNotes($status);

                    $duration = $endHour - $startHour;
                    $parentId = null;

                    for ($i = 0; $i < $duration; $i++) {
                        $currentStartHour = str_pad($startHour + $i, 2, '0', STR_PAD_LEFT).':00:00';
                        $currentEndHour = str_pad($startHour + $i + 1, 2, '0', STR_PAD_LEFT).':00:00';

                        $shiftNotes = $notes;
                        if ($duration > 1) {
                            $shiftNotes = ($notes ? $notes.' - ' : '').'Partie '.($i + 1).'/'.$duration;
                        }

                        $shiftData = [
                            'planning_id' => $planning->id,
                            'course_class_id' => $class->id,
                            'room_id' => $room->id,
                            'course_id' => $course->id,
                            'teacher_id' => $teacher->id,
                            'date' => $currentDate->format('Y-m-d'),
                            'starting_hour' => $currentStartHour,
                            'ending_hour' => $currentEndHour,
                            'number_teachers' => 1,
                            'status' => $status,
                            'notes' => $shiftNotes,
                            'parent_id' => $parentId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if ($parentId === null) {
                            $parentId = DB::table('planning_shift_plannings')->insertGetId($shiftData);
                        } else {
                            $shiftPlannings[] = $shiftData;
                        }

                        $totalCreated++;
                    }
                }
            }
        }

        if (! empty($shiftPlannings)) {
            foreach (array_chunk($shiftPlannings, 50) as $chunk) {
                DB::table('planning_shift_plannings')->insert($chunk);
            }
        }

        $this->command->info("Shift plannings seeded successfully! ({$totalCreated} sessions created)");
    }

    private function getRandomTeacherWithCourse($teachers)
    {
        $teachersWithCourses = $teachers->filter(function ($teacher) {
            return $teacher->courses->isNotEmpty();
        });

        if ($teachersWithCourses->isEmpty()) {
            return null;
        }

        return $teachersWithCourses->random();
    }

    private function determineStatus($date, $startTime): string
    {
        $now = Carbon::now();
        $sessionDateTime = Carbon::parse($date->format('Y-m-d').' '.$startTime);

        if ($sessionDateTime->lt($now)) {
            $rand = rand(1, 100);
            if ($rand <= 80) {
                return 'completed';
            }
            if ($rand <= 95) {
                return 'canceled';
            }

            return 'completed';
        } elseif ($sessionDateTime->isToday()) {
            $rand = rand(1, 100);
            if ($rand <= 30) {
                return 'ongoing';
            }

            return 'pending';
        }

        return 'pending';
    }

    private function generateNotes(string $status): ?string
    {
        if ($status === 'canceled') {
            $reasons = [
                'Professeur absent',
                'Salle indisponible',
                'Annulé pour cause de grève',
                'Reporté à une date ultérieure',
                'Maintenance urgente de la salle',
            ];

            return $reasons[array_rand($reasons)];
        }

        if ($status === 'completed') {
            $notes = [
                'Cours terminé normalement',
                'Session complétée avec succès',
                null,
                null,
                null,
            ];

            return $notes[array_rand($notes)];
        }

        return null;
    }
}
