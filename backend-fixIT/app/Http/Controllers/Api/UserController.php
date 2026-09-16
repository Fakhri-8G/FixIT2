<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = User::withCount('reports');

            // 🔍 Search berdasarkan nama atau email
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('email', 'like', "%{$keyword}%");
                });
            }

            // 🏷️ Filter berdasarkan role
            if ($request->filled('role')) {
                $request->validate([
                    'role' => 'in:admin,user',
                ]);

                $query->where('role', $request->role);
            }

            $users = $query->latest()->get();

            return $this->success($users, 'Daftar pengguna berhasil diambil.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    //  Detail 1 user beserta laporan-laporannya
    public function show(Request $request, User $user)
    {
        try {
            $user->loadCount('reports');
            $user->load(['reports' => function ($query) {
                $query->with(['category', 'location', 'images'])->latest();
            }]);

            return $this->success($user, 'Detail pengguna berhasil diambil.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }
}