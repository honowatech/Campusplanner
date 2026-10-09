<?php

namespace Database\Seeders;

use App\Models\CourseClass;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firstNames = [
            'male' => ['Alexandre', 'Baptiste', 'Charles', 'David', 'Ethan', 'Fabien', 'Gabriel', 'Hugo', 'Isaac', 'Julien', 'Kevin', 'Louis', 'Matthieu', 'Nathan', 'Olivier', 'Paul', 'Quentin', 'Raphael', 'Simon', 'Theo'],
            'female' => ['Alice', 'Barbara', 'Camille', 'Daphne', 'Emma', 'Fanny', 'Gabrielle', 'Helene', 'Ines', 'Juliette', 'Kelly', 'Laura', 'Marie', 'Noemie', 'Ophelie', 'Pauline', 'Quentine', 'Romane', 'Sarah', 'Tiffany'],
        ];

        $lastNames = ['Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Dubois', 'Moreau', 'Laurent', 'Simon', 'Michel', 'Lefebvre', 'Leroy', 'Roux', 'David', 'Bertrand', 'Morel', 'Fournier', 'Girard'];

        $classes = CourseClass::all();
        $studentCount = 0;

        // Le rôle `etudiant` est scopé au tenant : on l'assigne dans le contexte
        // du tenant démo. Les classes sont lues AVANT (tenant_id encore NULL ici,
        // le backfill `TenantSeeder` intervenant plus tard).
        $demo = Tenant::where('slug', 'demo')->firstOrFail();
        setPermissionsTeamId($demo->id);

        foreach ($classes as $class) {
            $studentsToCreate = min($class->capacity - 5, 25); // Leave some spots available

            for ($i = 0; $i < $studentsToCreate; $i++) {
                $gender = array_rand(['male' => 1, 'female' => 1]) === 'male' ? 'male' : 'female';
                $firstName = $firstNames[$gender][array_rand($firstNames[$gender])];
                $lastName = $lastNames[array_rand($lastNames)];
                $matricule = 'STD'.date('Y').str_pad(++$studentCount, 4, '0', STR_PAD_LEFT);

                // Create user account
                $user = User::create([
                    'name' => $firstName.' '.$lastName,
                    'email' => strtolower($firstName.'.'.$lastName.$studentCount.'@campustrack.com'),
                    'password' => Hash::make('password123'),
                ]);

                // Assign student role
                $user->assignRole('etudiant');

                // Create student record
                Student::create([
                    'user_id' => $user->id,
                    'course_class_id' => $class->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'phone' => '07'.rand(10000000, 99999999),
                    'date_of_birth' => now()->subYears(rand(18, 25))->subDays(rand(0, 365)),
                    'gender' => $gender,
                    'address' => rand(1, 100).' Rue de Paris, 75000 Paris',
                    'parent_name' => $lastNames[array_rand($lastNames)].' (Parent)',
                    'parent_phone' => '06'.rand(10000000, 99999999),
                    'matricule' => $matricule,
                    'admission_date' => now()->subMonths(rand(6, 18)),
                    'is_active' => true,
                ]);
            }
        }

        setPermissionsTeamId(null);

        $this->command->info("Students seeded successfully! ({$studentCount} students created with user accounts)");
    }
}
