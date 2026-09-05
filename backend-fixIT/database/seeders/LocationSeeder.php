<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['name' => 'Kelas XII RPL 1', 'description' => 'Ruang kelas jurusan RPL'],
            ['name' => 'Lab Komputer', 'description' => 'Laboratorium praktik komputer'],
            ['name' => 'Perpustakaan', 'description' => 'Ruang baca dan koleksi buku'],
            ['name' => 'Toilet Lantai 1', 'description' => 'Toilet dekat kantin'],
            ['name' => 'Lapangan', 'description' => 'Lapangan upacara dan olahraga'],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
