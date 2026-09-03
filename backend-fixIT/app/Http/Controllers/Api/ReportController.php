<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportImage;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    use ApiResponse;

    // USER: lihat laporan miliknya | ADMIN: lihat semua
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = Report::with(['category', 'location', 'images']);

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
                'images' => 'required|array|min:1|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $report = Report::create([
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'location_id' => $validated['location_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => 'reported',
            ]);

            // simpan tiap gambar ke storage & tabel report_images
            foreach ($request->file('images') as $image) {
                $path = $image->store('reports', 'public');

                $report->images()->create([
                    'image_path' => $path,
                ]);
            }

            return $this->success($report->load('images'), 'Laporan berhasil dibuat.', 201);
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

            $report->load(['user', 'category', 'location', 'updates.admin', 'images']);

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

             // hapus semua file gambar fisik dari storage
            foreach ($report->images as $img) {
                Storage::disk('public')->delete($img->image_path);
            }

            $report->delete();

            return $this->success(null, 'Laporan berhasil dihapus.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    // Endpoint Hapus 1 Gambar Saja
    public function deleteImage(Request $request, ReportImage $image)
    {
        try {
            $report = $image->report;

            if ($report->user_id !== $request->user()->id) {
                return $this->error('Anda tidak memiliki akses.', 403);
            }

            Storage::disk('public')->delete($image->image_path);
            $image->delete();

            return $this->success(null, 'Gambar berhasil dihapus.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }
}