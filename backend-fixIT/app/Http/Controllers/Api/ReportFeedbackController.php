<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportFeedbackController extends Controller
{
    use ApiResponse;

    // USER: beri feedback untuk laporan yang sudah selesai
    public function storeFeedback(Request $request, Report $report)
    {
        try {
            if ($report->user_id !== $request->user()->id) {
                return $this->error('Anda tidak memiliki akses ke laporan ini.', 403);
            }

            if ($report->status !== 'completed') {
                return $this->error('Feedback hanya bisa diberikan untuk laporan yang sudah selesai.', 422);
            }

            if ($report->feedbacks()->where('type', 'feedback')->exists()) {
                return $this->error('Anda sudah memberikan feedback untuk laporan ini.', 422);
            }

            $validated = $request->validate([
                'rating' => 'required|integer|between:1,5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $feedback = $report->feedbacks()->create([
                'user_id' => $request->user()->id,
                'type' => 'feedback',
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

            return $this->success($feedback, 'Terima kasih atas feedback Anda.', 201);
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    // USER: komplain karena perbaikan dirasa belum beres
    public function storeComplaint(Request $request, Report $report)
    {
        try {
            if ($report->user_id !== $request->user()->id) {
                return $this->error('Anda tidak memiliki akses ke laporan ini.', 403);
            }

            if ($report->status !== 'completed') {
                return $this->error('Komplain hanya bisa diajukan untuk laporan yang sudah selesai.', 422);
            }

            if ($report->feedbacks()->where('type', 'feedback')->exists()) {
                return $this->error('Anda sudah memberi feedback puas untuk laporan ini.', 422);
            }

            $validated = $request->validate([
                'comment' => 'required|string|min:10|max:1000',
            ]);

            $complaint = DB::transaction(function () use ($request, $report, $validated) {
                $complaint = $report->feedbacks()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'complaint',
                    'comment' => $validated['comment'],
                ]);

                // Laporan dibuka kembali supaya admin menindaklanjuti
                $report->update(['status' => 'processing']);

                // Dicatat di riwayat (admin_id null = aksi dari pelapor, bukan admin)
                $report->updates()->create([
                    'admin_id' => null,
                    'status' => 'processing',
                    'note' => 'Komplain dari pelapor: ' . $validated['comment'],
                ]);

                return $complaint;
            });

            return $this->success($complaint, 'Komplain berhasil diajukan, laporan dibuka kembali.', 201);
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }
}
