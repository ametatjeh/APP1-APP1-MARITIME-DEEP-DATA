<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Dr. Rahmat Hidayat (Super Admin)',
                'email' => 'admin@maritimedeepdata.org',
                'password' => Hash::make('SecretAdmin2026!'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
            ],
            [
                'name' => 'Elena Rostova (Research Director)',
                'email' => 'director@maritimedeepdata.org',
                'password' => Hash::make('DirectorDeepData2026!'),
                'role' => UserRole::RESEARCH_DIRECTOR,
                'is_active' => true,
            ],
            [
                'name' => 'Budi Santoso (Senior Investigator)',
                'email' => 'senior.inv@maritimedeepdata.org',
                'password' => Hash::make('InvestigatorSenior2026!'),
                'role' => UserRole::SENIOR_INVESTIGATOR,
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Jenkins (Investigator)',
                'email' => 'investigator@maritimedeepdata.org',
                'password' => Hash::make('InvestigatorPass2026!'),
                'role' => UserRole::INVESTIGATOR,
                'is_active' => true,
            ],
            [
                'name' => 'Aditya Pratama (Research Analyst)',
                'email' => 'analyst@maritimedeepdata.org',
                'password' => Hash::make('AnalystPass2026!'),
                'role' => UserRole::RESEARCH_ANALYST,
                'is_active' => true,
            ],
            [
                'name' => 'Fiona Gallagher (Evidence Manager)',
                'email' => 'evidence@maritimedeepdata.org',
                'password' => Hash::make('EvidencePass2026!'),
                'role' => UserRole::EVIDENCE_MANAGER,
                'is_active' => true,
            ],
            [
                'name' => 'Guest Observer (Viewer)',
                'email' => 'viewer@maritimedeepdata.org',
                'password' => Hash::make('ViewerPass2026!'),
                'role' => UserRole::VIEWER,
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
