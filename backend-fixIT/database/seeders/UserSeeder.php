<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Akun admin
        User::create([
            'name' => 'Admin FixIT',
            'email' => 'admin@fixit.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Akun user biasa
        User::create([
            'name' => 'Fakhri',
            'email' => 'fakhri@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        User::create([
            'name' => 'Fadil',
            'email' => 'fadil@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
    }
}
