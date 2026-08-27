<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default
        $admin = User::firstOrCreate(
            ['email' => 'admin@kebuntebu.id'],
            [
                'name'         => 'Administrator Kebun',
                'password'     => Hash::make('password'),
                'role'         => 'admin',
                'phone_number' => '08123456789',
            ]
        );
        $admin->assignRole('admin');

        // Petugas demo
        $officer = User::firstOrCreate(
            ['email' => 'petugas@kebuntebu.id'],
            [
                'name'         => 'Petugas Lapangan',
                'password'     => Hash::make('password'),
                'role'         => 'field_officer',
                'phone_number' => '08987654321',
            ]
        );
        $officer->assignRole('field_officer');
    }
}
