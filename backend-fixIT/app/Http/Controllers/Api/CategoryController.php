<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $categories = Category::latest()->get();
            return $this->success($categories, 'Daftar kategori berhasil diambil.');
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
            ]);

            $category = Category::create($validated);

            return $this->success($category, 'Kategori berhasil ditambahkan.', 201);
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function show(Category $category)
    {
        return $this->success($category, 'Detail kategori berhasil diambil.');
    }

    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            ]);

            $category->update($validated);

            return $this->success($category, 'Kategori berhasil diperbarui.');
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal.', 422, $e->errors());
        } catch (Exception $e) {
            return $this->error('Terjadi kesalahan pada server.', 500);
        }
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();
            return $this->success(null, 'Kategori berhasil dihapus.');
        } catch (Exception $e) {
            return $this->error('Tidak bisa menghapus kategori ini.', 500);
        }
    }
}