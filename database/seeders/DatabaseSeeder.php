<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Student Demo', 'email' => 'student@example.com', 'role' => UserRole::Student],
            ['name' => 'Organizer Demo', 'email' => 'organizer@example.com', 'role' => UserRole::Organizer],
            ['name' => 'Administrator Demo', 'email' => 'admin@example.com', 'role' => UserRole::Administrator],
        ] as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [...$account, 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
        }
    }
}
