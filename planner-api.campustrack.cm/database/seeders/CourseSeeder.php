<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            // Informatique
            ['name' => 'Algorithmique', 'code' => 'ALGO', 'dept' => 'INFO', 'coef' => 3, 'hours' => 4],
            ['name' => 'Programmation Web', 'code' => 'PROG-WEB', 'dept' => 'INFO', 'coef' => 2, 'hours' => 3],
            ['name' => 'Base de données', 'code' => 'BDD', 'dept' => 'INFO', 'coef' => 3, 'hours' => 4],
            ['name' => 'Réseaux', 'code' => 'RESEAU', 'dept' => 'INFO', 'coef' => 2, 'hours' => 3],
            ['name' => 'Intelligence Artificielle', 'code' => 'IA', 'dept' => 'INFO', 'coef' => 2, 'hours' => 2],

            // Mathématiques
            ['name' => 'Algèbre', 'code' => 'ALG', 'dept' => 'MATH', 'coef' => 3, 'hours' => 4],
            ['name' => 'Analyse', 'code' => 'ANAL', 'dept' => 'MATH', 'coef' => 3, 'hours' => 4],
            ['name' => 'Probabilités', 'code' => 'PROBA', 'dept' => 'MATH', 'coef' => 2, 'hours' => 3],
            ['name' => 'Statistiques', 'code' => 'STAT', 'dept' => 'MATH', 'coef' => 2, 'hours' => 3],

            // Physique
            ['name' => 'Mécanique', 'code' => 'MECA', 'dept' => 'PHYS', 'coef' => 3, 'hours' => 4],
            ['name' => 'Électromagnétisme', 'code' => 'ELEC', 'dept' => 'PHYS', 'coef' => 3, 'hours' => 4],
            ['name' => 'Thermodynamique', 'code' => 'THERMO', 'dept' => 'PHYS', 'coef' => 2, 'hours' => 3],

            // Langues
            ['name' => 'Anglais', 'code' => 'ANGL', 'dept' => 'LANG', 'coef' => 2, 'hours' => 2],
            ['name' => 'Français', 'code' => 'FRAN', 'dept' => 'LANG', 'coef' => 2, 'hours' => 2],
            ['name' => 'Espagnol', 'code' => 'ESP', 'dept' => 'LANG', 'coef' => 1, 'hours' => 2],

            // Économie
            ['name' => 'Microéconomie', 'code' => 'MICRO', 'dept' => 'ECO', 'coef' => 3, 'hours' => 4],
            ['name' => 'Macroéconomie', 'code' => 'MACRO', 'dept' => 'ECO', 'coef' => 3, 'hours' => 4],
            ['name' => 'Comptabilité', 'code' => 'COMPTA', 'dept' => 'ECO', 'coef' => 2, 'hours' => 3],
        ];

        foreach ($courses as $course) {
            $department = Department::where('code', $course['dept'])->first();

            Course::firstOrCreate(
                ['code' => $course['code']],
                [
                    'name' => $course['name'],
                    'code' => $course['code'],
                    'description' => 'Matière du département '.$course['dept'],
                    'department_id' => $department?->id,
                    'coefficient' => $course['coef'],
                    'hours_per_week' => $course['hours'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Courses seeded successfully!');
    }
}
