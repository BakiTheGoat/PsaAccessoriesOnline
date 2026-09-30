<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seeds only the admin account. There's no self-registration path
     * for staff/admin (see AuthController — signup only offers buyer),
     * so this is the one account that has to exist from the start.
     * Create staff accounts from the admin panel once logged in.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Site Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
