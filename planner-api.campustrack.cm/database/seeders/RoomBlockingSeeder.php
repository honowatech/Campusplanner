<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomBlocking;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomBlockingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'superadmin@campustrack.com')->first();

        if (! $admin) {
            $this->command->warn('Super admin user not found. Skipping RoomBlockingSeeder.');

            return;
        }

        $blockings = [
            // Maintenance blockings
            [
                'room_code' => 'LAB-INFO-1',
                'start' => now()->addDays(2)->setTime(8, 0),
                'end' => now()->addDays(2)->setTime(12, 0),
                'reason' => 'Computer maintenance and software updates',
                'type' => 'maintenance',
            ],
            [
                'room_code' => 'LAB-PHYS',
                'start' => now()->addDays(5)->setTime(14, 0),
                'end' => now()->addDays(5)->setTime(18, 0),
                'reason' => 'Equipment inspection',
                'type' => 'maintenance',
            ],

            // Event blockings
            [
                'room_code' => 'AMP-A',
                'start' => now()->addDays(7)->setTime(9, 0),
                'end' => now()->addDays(7)->setTime(17, 0),
                'reason' => 'Annual Science Conference',
                'type' => 'event',
            ],
            [
                'room_code' => 'CONF-1',
                'start' => now()->addDays(3)->setTime(10, 0),
                'end' => now()->addDays(3)->setTime(12, 0),
                'reason' => 'Department meeting',
                'type' => 'event',
            ],

            // Holiday blockings
            [
                'room_code' => 'A-101',
                'start' => now()->addDays(10)->setTime(0, 0),
                'end' => now()->addDays(10)->setTime(23, 59),
                'reason' => 'Public Holiday - Room closed',
                'type' => 'holiday',
            ],
            [
                'room_code' => 'A-102',
                'start' => now()->addDays(10)->setTime(0, 0),
                'end' => now()->addDays(10)->setTime(23, 59),
                'reason' => 'Public Holiday - Room closed',
                'type' => 'holiday',
            ],

            // Other blockings
            [
                'room_code' => 'STUDY-1',
                'start' => now()->addDays(1)->setTime(13, 0),
                'end' => now()->addDays(1)->setTime(15, 0),
                'reason' => 'Reserved for exam preparation',
                'type' => 'other',
            ],
        ];

        foreach ($blockings as $blockingData) {
            $room = Room::where('code', $blockingData['room_code'])->first();

            if ($room) {
                RoomBlocking::firstOrCreate(
                    [
                        'room_id' => $room->id,
                        'start_datetime' => $blockingData['start'],
                        'end_datetime' => $blockingData['end'],
                    ],
                    [
                        'room_id' => $room->id,
                        'start_datetime' => $blockingData['start'],
                        'end_datetime' => $blockingData['end'],
                        'reason' => $blockingData['reason'],
                        'blocking_type' => $blockingData['type'],
                        'created_by' => $admin->id,
                        'is_recurring' => false,
                    ]
                );
            }
        }

        $this->command->info('Room blockings seeded successfully! ('.count($blockings).' blockings created)');
    }
}
