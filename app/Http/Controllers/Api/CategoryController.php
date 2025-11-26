<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Tampilkan semua kategori milik user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $categories = Category::where('user_id', $user->id)
            ->withCount('tasks')  // → otomatis mengisi tasks_count
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($categories, 200);
    }

    /**
     * Simpan kategori baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Tampilkan detail kategori (opsional, jarang dipakai).
     */
    public function show(Category $category, Request $request)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->loadCount('tasks');
        return response()->json($category, 200);
    }

    /**
     * Update kategori.
     */
    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $validated['name'],
        ]);

        return response()->json($category, 200);
    }

    /**
     * Hapus kategori.
     * NOTE: Tugas yang memakai kategori ini TIDAK dihapus → hanya relasinya hilang.
     */
    public function destroy(Category $category, Request $request)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->tasks()->detach(); // hapus relasi pivot
        $category->delete();

        return response()->json(null, 204);
    }
}
