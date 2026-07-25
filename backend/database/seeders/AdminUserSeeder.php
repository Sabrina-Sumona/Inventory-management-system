<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'admin@deshsolar.com',
            ],
            [
                'name' => 'Desh Solar Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'email_verified_at' => now(),
            ]
        );
    }
}