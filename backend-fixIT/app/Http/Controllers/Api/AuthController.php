<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'user',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->success([
                'user' => $user,
                'token' => $token,
            ], 'Registrasi berhasil.', 201);
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return $this->error('Email atau password salah.', 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->success([
                'user' => $user,
                'token' => $token,
            ], 'Login berhasil.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->success(null, 'Logout berhasil.');
        } catch (Exception $e) {
            return $this->error('Gagal melakukan logout.', 500);
        }
    }

    public function profile(Request $request)
    {
        return $this->success($request->user(), 'Data profil berhasil diambil.');
    }
}