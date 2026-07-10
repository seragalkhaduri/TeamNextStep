<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed demo users for development and testing.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'username' => 'sysadmin',
                'email' => 'sysadmin@uimp.edu.ly',
                'password_hash' => Hash::make('password'),
                'preferred_language' => 'en',
                'is_active' => true,
                'roles' => ['SYSTEM_ADMIN'],
            ],
            [
                'username' => 'uniadmin',
                'email' => 'uniadmin@uimp.edu.ly',
                'password_hash' => Hash::make('password'),
                'preferred_language' => 'ar',
                'is_active' => true,
                'roles' => ['UNIVERSITY_ADMIN'],
            ],
            [
                'username' => 'registrar',
                'email' => 'registrar@uimp.edu.ly',
                'password_hash' => Hash::make('password'),
                'preferred_language' => 'ar',
                'is_active' => true,
                'roles' => ['REGISTRAR_STAFF'],
            ],
            [
                'username' => 'hrstaff',
                'email' => 'hrstaff@uimp.edu.ly',
                'password_hash' => Hash::make('password'),
                'preferred_language' => 'ar',
                'is_active' => true,
                'roles' => ['HR_STAFF'],
            ],
            [
                'username' => 'auditor',
                'email' => 'auditor@uimp.edu.ly',
                'password_hash' => Hash::make('password'),
                'preferred_language' => 'en',
                'is_active' => true,
                'roles' => ['AUDITOR'],
            ],
            [
                'username' => 'student01',
                'email' => 'student01@uimp.edu.ly',
                'password_hash' => Hash::make('password'),
                'preferred_language' => 'ar',
                'is_active' => true,
                'roles' => ['STUDENT'],
            ],
        ];

        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            $user = User::firstOrCreate(
                ['username' => $userData['username']],
                $userData
            );

            $user->syncRoles($roles);
        }

        $this->command->info('✅ Demo users seeded: ' . count($users) . ' users.');
    }
}
