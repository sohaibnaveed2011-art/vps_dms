<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'naveed', 'email' => 'naveed@gmail.com', 'is_admin' => true],
            ['name' => 'umair', 'email' => 'umair@gmail.com'],
            ['name' => 'khansa', 'email' => 'khansa@gmail.com'],
            ['name' => 'hassan', 'email' => 'hassan@gmail.com'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_admin' => $data['is_admin'] ?? false,
                    'is_active' => true,
                ]
            );
        }
    }
}
