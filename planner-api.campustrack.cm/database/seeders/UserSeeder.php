<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $departments = Department::all();

        $rolesByDepartment = [
            'administrateur',
            'responsable-departement',
            'personnel-administratif',
            'professeur',
        ];

        $userTemplates = [
            'administrateur' => [
                'first_name' => 'Admin',
                'name_template' => 'Admin {dept}',
            ],
            'responsable-departement' => [
                'first_name' => 'Responsable',
                'name_template' => 'Responsable {dept}',
            ],
            'personnel-administratif' => [
                'first_name' => 'Personnel',
                'name_template' => 'Personnel {dept}',
            ],
            'professeur' => [
                'first_name' => 'Prof',
                'name_template' => 'Prof {dept}',
            ],
        ];

        $userCount = 0;

        foreach ($departments as $department) {
            foreach ($rolesByDepartment as $role) {
                $template = $userTemplates[$role];
                $name = str_replace('{dept}', $department->name, $template['name_template']);
                $email = strtolower(
                    str_replace(' ', '.', $template['first_name']).'.'.
                    str_replace(' ', '.', strtolower($department->code)).'@campustrack.com'
                );

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'department_id' => $department->id,
                        'email_verified_at' => now(),
                    ]
                );

                $user->assignRole($role);
                $userCount++;
            }
        }

        $this->command->info("Users seeded successfully! ({$userCount} users created with roles)");
    }
}
