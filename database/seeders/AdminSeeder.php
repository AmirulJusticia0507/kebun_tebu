<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default
        User::firstOrCreate(
            ['email' => 'admin@kebuntebu.id'],
            [
                'name'         => 'Administrator',
                'password'     => Hash::make('password'),
                'role'         => 'admin',
                'phone_number' => '08123456789',
            ]
        );

        // Petugas demo
        User::firstOrCreate(
            ['email' => 'petugas@kebuntebu.id'],
            [
                'name'         => 'Petugas Lapangan',
                'password'     => Hash::make('password'),
                'role'         => 'field_officer',
                'phone_number' => '08987654321',
            ]
        );
    }
}
