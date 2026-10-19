<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin Demo (admin@jejakbarang.test / password123)
        User::firstOrCreate(
            ['email' => 'admin@jejakbarang.test'],
            [
                'name' => 'Admin JejakBarang',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone_number' => '081122334455',
            ]
        );

        // 2. Akun Mahasiswa Demo (mahasiswa@jejakbarang.test / password123)
        User::firstOrCreate(
            ['email' => 'mahasiswa@jejakbarang.test'],
            [
                'name' => 'Mahasiswa Demo',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone_number' => '081298765432',
            ]
        );
    }
}
