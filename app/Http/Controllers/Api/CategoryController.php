<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category; // <-- 1. Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- 2. Tambahkan ini

class CategoryController extends Controller
{
    /**
     * Menampilkan SEMUA kategori (milik user yang login)
     */
    public function index()
    {
        // Ambil semua kategori yang HANYA dimiliki oleh user yg login
        return Auth::user()->categories()->orderBy('name', 'asc')->get();
    }

    /**
     * Menyimpan kategori BARU (milik user yang login)
     */
    public function store(Request $request)
    {
        // Validasi (nama wajib, dan unik UNTUK user tsb)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Buat kategori baru MENGGUNAKAN RELASI 'categories()'
        // Ini otomatis mengisi 'user_id'
        $category = Auth::user()->categories()->create($validatedData);

        return response()->json($category, 201); // 201 = Created
    }

    /**
     * Menampilkan SATU kategori (dan cek kepemilikan)
     */
    public function show(Category $category)
    {
        // 1. Cek apakah kategori ini milik user yang sedang login
        if (Auth::id() !== $category->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403); // 403 = Forbidden
        }
        return $category;
    }

    /**
     * Meng-UPDATE kategori (dan cek kepemilikan)
     */
    public function update(Request $request, Category $category)
    {
        // 1. Cek kepemilikan
        if (Auth::id() !== $category->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403); // 403 = Forbidden
        }

        // 2. Validasi data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // 3. Update data
        $category->update($validatedData);

        return response()->json($category, 200); // 200 = OK
    }

    /**
     * Menghapus kategori (dan cek kepemilikan)
     */
    public function destroy(Category $category)
    {
        // 1. Cek kepemilikan
        if (Auth::id() !== $category->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403); // 403 = Forbidden
        }

        // 2. Hapus kategori
        // (Relasi di database 'onDelete('cascade')' akan
        // otomatis menghapus datanya dari tabel pivot 'category_task')
        $category->delete();

        return response()->json(null, 204); // 204 = No Content
    }
}