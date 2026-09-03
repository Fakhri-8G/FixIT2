<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $locations = Location::latest()->get();
            return $this->success($locations, 'Daftar lokasi berhasil diambil.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:locations,name',
                'description' => 'nullable|string',
            ]);

            $location = Location::create($validated);

            return $this->success($location, 'Lokasi berhasil ditambahkan.', 201);
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function show(Location $location)
    {
        return $this->success($location, 'Detail lokasi berhasil diambil.');
    }

    public function update(Request $request, Location $location)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
                'description' => 'nullable|string',
            ]);

            $location->update($validated);

            return $this->success($location, 'Lokasi berhasil diperbarui.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function destroy(Location $location)
    {
        try {
            $location->delete();
            return $this->success(null, 'Lokasi berhasil dihapus.');
        } catch (Exception $e) {
            return $this->error('Tidak bisa menghapus lokasi ini.', 500);
        }
    }
}