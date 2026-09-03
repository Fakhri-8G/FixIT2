<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;

class ReportUpdateController extends Controller
{
    use ApiResponse;

    // Lihat riwayat perubahan status dari sebuah laporan
    public function index(Request $request, Report $report)
    {
        try {
            $user = $request->user();

            // User biasa hanya boleh lihat riwayat laporannya sendiri
            if ($user->role !== 'admin' && $report->user_id !== $user->id) {
                return $this->error('Anda tidak memiliki akses ke laporan ini.', 403);
            }

            $updates = $report->updates()->with('admin')->latest()->get();
            return $this->success($updates, 'Riwayat status laporan berhasil diambil.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }
}