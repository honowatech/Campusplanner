<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\TeacherBlocking;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherBlockingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'superadmin@campustrack.com')->first();

        if (! $admin) {
            $this->command->warn('Super admin user not found. Skipping TeacherBlockingSeeder.');

            return;
        }

        // Get some teachers
        $teachers = Teacher::limit(5)->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Skipping TeacherBlockingSeeder.');

            return;
        }

        $blockings = [
            // Pending blockings
            [
                'teacher_email' => $teachers[0]->email,
                'start' => now()->addDays(3)->setTime(8, 0),
                'end' => now()->addDays(3)->setTime(17, 0),
                'reason' => 'Medical appointment',
                'type' => 'medical',
                'status' => 'pending',
            ],
            [
                'teacher_email' => $teachers[1]->email,
                'start' => now()->addDays(5)->setTime(9, 0),
                'end' => now()->addDays(5)->setTime(12, 0),
                'reason' => 'Professional training session',
                'type' => 'training',
                'status' => 'pending',
            ],

            // Approved blockings
            [
                'teacher_email' => $teachers[2]->email,
                'start' => now()->addDays(7)->setTime(0, 0),
                'end' => now()->addDays(10)->setTime(23, 59),
                'reason' => 'Annual vacation',
                'type' => 'vacation',
                'status' => 'approved',
            ],
            [
                'teacher_email' => $teachers[3]->email,
                'start' => now()->addDays(2)->setTime(14, 0),
                'end' => now()->addDays(2)->setTime(16, 0),
                'reason' => 'Conference participation',
                'type' => 'other',
                'status' => 'approved',
            ],

            // Rejected blocking
            [
                'teacher_email' => $teachers[4]->email,
                'start' => now()->addDays(1)->setTime(8, 0),
                'end' => now()->addDays(1)->setTime(17, 0),
                'reason' => 'Personal appointment',
                'type' => 'absence',
                'status' => 'rejected',
                'rejection_reason' => 'Too short notice, exams period',
            ],
        ];

        foreach ($blockings as $blockingData) {
            $teacher = Teacher::where('email', $blockingData['teacher_email'])->first();

            if ($teacher) {
                $data = [
                    'teacher_id' => $teacher->id,
                    'start_datetime' => $blockingData['start'],
                    'end_datetime' => $blockingData['end'],
                    'reason' => $blockingData['reason'],
                    'blocking_type' => $blockingData['type'],
                    'status' => $blockingData['status'],
                ];

                if ($blockingData['status'] === 'approved') {
                    $data['approved_by'] = $admin->id;
                    $data['approved_at'] = now()->subHours(rand(1, 24));
                } elseif ($blockingData['status'] === 'rejected') {
                    $data['approved_by'] = $admin->id;
                    $data['approved_at'] = now()->subHours(rand(1, 24));
                    $data['rejection_reason'] = $blockingData['rejection_reason'] ?? null;
                }

                TeacherBlocking::firstOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'start_datetime' => $blockingData['start'],
                        'end_datetime' => $blockingData['end'],
                    ],
                    $data
                );
            }
        }

        $this->command->info('Teacher blockings seeded successfully! ('.count($blockings).' blockings created)');
    }
}
