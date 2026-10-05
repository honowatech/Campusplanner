<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    /**
     * Rôles proposés en mode démo (tous les rôles sauf `super-admin`).
     */
    private const DEMO_ACCOUNTS = [
        // 'administrateur' => ['name' => 'Démo Administrateur', 'email' => 'demo.administrateur@campustrack.com'],
        'responsable-departement' => ['name' => 'Démo Responsable', 'email' => 'demo.responsable@campustrack.com'],
        'personnel-administratif' => ['name' => 'Démo Personnel', 'email' => 'demo.personnel@campustrack.com'],
        'professeur' => ['name' => 'Démo Professeur', 'email' => 'demo.professeur@campustrack.com'],
        'etudiant' => ['name' => 'Démo Étudiant', 'email' => 'demo.etudiant@campustrack.com'],
    ];

    /**
     * Crée (ou met à jour) un compte démo par rôle, distinct du super-admin.
     */
    public function run(): void
    {
        $departmentId = Department::query()->orderBy('id')->value('id');

        foreach (self::DEMO_ACCOUNTS as $role => $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_approved' => true,
                    'department_id' => $departmentId,
                ]
            );

            // Maintient le rôle et l'approbation, même si le compte existait déjà.
            $user->assignRole($role);
            $user->update(['is_approved' => true, 'is_demo' => true, 'department_id' => $departmentId]);

            $this->command->info("Demo account [{$role}] ready: {$account['email']}");
        }
    }
}
