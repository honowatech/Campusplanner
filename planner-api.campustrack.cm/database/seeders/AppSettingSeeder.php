<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $settings = AppSetting::instance();

        $settings->update([
            'demo_mode' => false,
            'max_weekly_hours_per_teacher' => 18,
            'max_consecutive_hours' => 4,
            'max_daily_hours_per_teacher' => 8,
            'max_daily_hours_per_class' => 8,
            'enable_room_conflict' => true,
            'enable_teacher_conflict' => true,
            'enable_group_conflict' => true,
            'sms_config' => [
                'apiKey' => 'demo-'.fake()->uuid(),
                'sender_id' => 'CAMPUS TRACK',
                'balance' => fake()->numberBetween(0, 5000),
            ],
        ]);

        $this->command->info('App settings seeded successfully!');
    }
}
