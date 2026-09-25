<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            // Informatique
            [
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont@campustrack.com',
                'speciality' => 'Développement Web',
                'dept' => 'INFO',
                'courses' => ['PROG-WEB', 'BDD'],
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'Martin',
                'email' => 'marie.martin@campustrack.com',
                'speciality' => 'Intelligence Artificielle',
                'dept' => 'INFO',
                'courses' => ['ALGO', 'IA'],
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Bernard',
                'email' => 'pierre.bernard@campustrack.com',
                'speciality' => 'Réseaux',
                'dept' => 'INFO',
                'courses' => ['RESEAU'],
            ],

            // Mathématiques
            [
                'first_name' => 'Sophie',
                'last_name' => 'Petit',
                'email' => 'sophie.petit@campustrack.com',
                'speciality' => 'Algèbre',
                'dept' => 'MATH',
                'courses' => ['ALG', 'ANAL'],
            ],
            [
                'first_name' => 'Lucas',
                'last_name' => 'Moreau',
                'email' => 'lucas.moreau@campustrack.com',
                'speciality' => 'Statistiques',
                'dept' => 'MATH',
                'courses' => ['PROBA', 'STAT'],
            ],

            // Physique
            [
                'first_name' => 'Emma',
                'last_name' => 'Richard',
                'email' => 'emma.richard@campustrack.com',
                'speciality' => 'Mécanique',
                'dept' => 'PHYS',
                'courses' => ['MECA'],
            ],
            [
                'first_name' => 'Hugo',
                'last_name' => 'Dubois',
                'email' => 'hugo.dubois@campustrack.com',
                'speciality' => 'Électromagnétisme',
                'dept' => 'PHYS',
                'courses' => ['ELEC', 'THERMO'],
            ],

            // Langues
            [
                'first_name' => 'Camille',
                'last_name' => 'Leroy',
                'email' => 'camille.leroy@campustrack.com',
                'speciality' => 'Anglais',
                'dept' => 'LANG',
                'courses' => ['ANGL'],
            ],
            [
                'first_name' => 'Louis',
                'last_name' => 'Garcia',
                'email' => 'louis.garcia@campustrack.com',
                'speciality' => 'Français',
                'dept' => 'LANG',
                'courses' => ['FRAN', 'ESP'],
            ],

            // Économie
            [
                'first_name' => 'Julie',
                'last_name' => 'Roux',
                'email' => 'julie.roux@campustrack.com',
                'speciality' => 'Microéconomie',
                'dept' => 'ECO',
                'courses' => ['MICRO'],
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Durand',
                'email' => 'thomas.durand@campustrack.com',
                'speciality' => 'Comptabilité',
                'dept' => 'ECO',
                'courses' => ['MACRO', 'COMPTA'],
            ],
        ];

        foreach ($teachers as $index => $teacherData) {
            $department = Department::where('code', $teacherData['dept'])->first();

            $teacher = Teacher::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'first_name' => $teacherData['first_name'],
                    'last_name' => $teacherData['last_name'],
                    'email' => $teacherData['email'],
                    'phone' => '012345678'.$index,
                    'speciality' => $teacherData['speciality'],
                    'department_id' => $department?->id,
                    'max_hours_per_week' => 16,
                    'is_active' => true,
                    'hired_at' => now()->subYears(rand(1, 10)),
                ]
            );

            // Assigner les matières
            $courseIds = Course::whereIn('code', $teacherData['courses'])
                ->pluck('id')
                ->toArray();

            $teacher->courses()->sync($courseIds);
        }

        $this->command->info('Teachers seeded successfully! (11 teachers créés)');
    }
}
