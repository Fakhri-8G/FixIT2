<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use ApiResponse;

    // Lihat profil (bisa juga tetap pakai yang di AuthController, ini alternatif kalau mau dipindah kesini)
    public function show(Request $request)
    {
        return $this->success($request->user(), 'Data profil berhasil diambil.');
    }

    // Update nama & email
    public function update(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
            ]);

            $user->update($validated);

            return $this->success($user, 'Profil berhasil diperbarui.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    // Ganti password
    public function updatePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->error('Password saat ini salah.', 422, [
                    'current_password' => ['Password saat ini tidak sesuai.']
                ]);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return $this->success(null, 'Password berhasil diperbarui.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }
}