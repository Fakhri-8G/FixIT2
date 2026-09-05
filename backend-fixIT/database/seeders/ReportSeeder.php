<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Report;
use App\Models\User;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('role', 'user')->first();
        $admin = User::where('role', 'admin')->first();

        $reports = [
            [
                'title' => 'Proyektor Tidak Menyala',
                'description' => 'Proyektor tidak dapat menyala ketika digunakan untuk presentasi.',
                'category' => 'Elektronik',
                'location' => 'Kelas XII RPL 1',
                'status' => 'reported',
            ],
            [
                'title' => 'Kursi Patah',
                'description' => 'Kursi di baris belakang kelas patah pada bagian kaki.',
                'category' => 'Furniture',
                'location' => 'Kelas XII RPL 1',
                'status' => 'verified',
            ],
            [
                'title' => 'Keran Air Bocor',
                'description' => 'Keran air di toilet terus menetes meski sudah ditutup rapat.',
                'category' => 'Sanitasi',
                'location' => 'Toilet Lantai 1',
                'status' => 'processing',
            ],
            [
                'title' => 'Lampu Mati',
                'description' => 'Lampu di ruang lab komputer mati sebelah.',
                'category' => 'Elektronik',
                'location' => 'Lab Komputer',
                'status' => 'completed',
            ],
        ];

        foreach ($reports as $item) {
            $report = Report::create([
                'user_id' => $user->id,
                'category_id' => Category::where('name', $item['category'])->first()->id,
                'location_id' => Location::where('name', $item['location'])->first()->id,
                'title' => $item['title'],
                'description' => $item['description'],
                'status' => $item['status'],
            ]);

            // kalau status bukan 'reported', bikin history update-nya juga
            if ($item['status'] !== 'reported') {
                $report->updates()->create([
                    'admin_id' => $admin->id,
                    'status' => $item['status'],
                    'note' => 'Update status otomatis dari seeder.',
                ]);
            }
        }
    }
}
