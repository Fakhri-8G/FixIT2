<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    use ApiResponse;

    // USER: lihat laporan miliknya | ADMIN: lihat semua
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = Report::with(['category', 'location']);

            if ($user->role === 'admin') {
                $query->with('user');
            } else {
                $query->where('user_id', $user->id);
            }

            $reports = $query->latest()->get();

            return $this->success($reports, 'Daftar laporan berhasil diambil.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'location_id' => 'required|exists:locations,id',
                'image' => 'nullable|image|max:2048',
            ]);

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('reports', 'public');
            }

            $report = Report::create([
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'location_id' => $validated['location_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'image' => $imagePath,
                'status' => 'reported',
            ]);

            return $this->success($report, 'Laporan berhasil dibuat.', 201);
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function show(Request $request, Report $report)
    {
        try {
            $user = $request->user();

            if ($user->role !== 'admin' && $report->user_id !== $user->id) {
                return $this->error('Anda tidak memiliki akses ke laporan ini.', 403);
            }

            $report->load(['user', 'category', 'location', 'updates.admin']);

            return $this->success($report, 'Detail laporan berhasil diambil.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    // ADMIN: update status laporan
    public function update(Request $request, Report $report)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return $this->error('Hanya admin yang bisa mengubah status laporan.', 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:reported,verified,processing,completed,rejected',
                'note' => 'nullable|string',
            ]);

            $report->update(['status' => $validated['status']]);

            $report->updates()->create([
                'admin_id' => $request->user()->id,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);

            return $this->success($report->load('updates'), 'Status laporan berhasil diperbarui.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    // USER: hapus laporan miliknya sendiri (hanya jika status masih 'reported')
    public function destroy(Request $request, Report $report)
    {
        try {
            if ($report->user_id !== $request->user()->id || $report->status !== 'reported') {
                return $this->error('Laporan ini tidak bisa dihapus.', 403);
            }

            $report->delete();

            return $this->success(null, 'Laporan berhasil dihapus.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }
}