<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        $seeders = [
            RolesAndPermissionsSeeder::class => 'Departments, Roles, Permissions',
            SuperAdminSeeder::class => 'Super Admin + permission demo-mode',
            DemoAccountSeeder::class => 'Demo accounts (1 per role, 5)',
            CourseSeeder::class => 'Courses (18)',
            RoomSeeder::class => 'Rooms (20)',
            TeacherSeeder::class => 'Teachers (11) + Course assignments',
            CourseClassSeeder::class => 'Classes (9) + Room assignments',
            UserSeeder::class => 'Users (20) with roles by department',
            StudentSeeder::class => 'Students (~200) + User accounts',
            TeacherBlockingSeeder::class => 'Teacher Blockings (5)',
            RoomBlockingSeeder::class => 'Room Blockings (7)',
        ];

        foreach ($seeders as $seeder => $description) {
            $this->command->info("📦 Running: {$description}");
            $this->call($seeder);
        }

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        $this->command->info('   - 5 Departments');
        $this->command->info('   - 6 Roles + ~70 Permissions');
        $this->command->info('   - 1 Super Admin (superadmin@campustrack.com / password)');
        $this->command->info('   - 5 Demo Accounts (1 per role, mode démo désactivé par défaut)');
        $this->command->info('   - 18 Courses');
        $this->command->info('   - 20 Rooms');
        $this->command->info('   - 11 Teachers');
        $this->command->info('   - 9 Classes');
        $this->command->info('   - 20 Test Users (4 roles × 5 departments)');
        $this->command->info('   - ~200 Students');
        $this->command->info('   - 5 Teacher Blockings');
        $this->command->info('   - 7 Room Blockings');
    }
}
