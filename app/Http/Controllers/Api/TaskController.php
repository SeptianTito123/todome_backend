<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- 1. TAMBAHKAN INI (WAJIB)

class TaskController extends Controller
{
    /**
     * Menampilkan SEMUA tugas (HANYA milik user yang login)
     */
    public function index()
    {
        $user = Auth::user();
        
        // Catatan: Tambahkan ->with('categories')
        // Ini "Eager Loading", memberitahu Laravel
        // untuk sekalian mengambil data kategori yang terhubung
        return $user->tasks()->with('categories')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Menyimpan tugas BARU (dan melampirkan kategori)
     */
    public function store(Request $request)
    {
        // 1. Validasi (tambahkan 'category_ids' opsional)
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'category_ids' => 'nullable|array', // Menerima array [1, 2, 3]
            'category_ids.*' => 'integer|exists:categories,id', // Cek tiap ID di array
        ]);

        // 2. Buat task
        $user = Auth::user();
        $task = $user->tasks()->create([
            'judul' => $validatedData['judul'],
            'deskripsi' => $validatedData['deskripsi'] ?? null,
            'deadline' => $validatedData['deadline'] ?? null,
        ]);

        // 3. (BARU) Lampirkan Kategori jika ada
        if (isset($validatedData['category_ids'])) {
            // 'attach' akan mengisi tabel pivot 'category_task'
            $task->categories()->attach($validatedData['category_ids']);
        }

        // 4. Kembalikan task LENGKAP dengan kategorinya
        return response()->json($task->load('categories'), 201);
    }

    /**
     * Menampilkan SATU tugas (dan kategorinya)
     */
    public function show(Task $task)
    {
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // Catatan: Load relasi 'categories' saat menampilkan
        return $task->load('categories');
    }

    /**
     * Meng-UPDATE tugas (dan kategorinya)
     */
    public function update(Request $request, Task $task)
    {
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $validatedData = $request->validate([
            'judul' => 'string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status_selesai' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        // 1. Update data task (pisahkan kategori)
        $task->update([
            'judul' => $validatedData['judul'] ?? $task->judul,
            'deskripsi' => $validatedData['deskripsi'] ?? $task->deskripsi,
            'deadline' => $validatedData['deadline'] ?? $task->deadline,
            'status_selesai' => $validatedData['status_selesai'] ?? $task->status_selesai,
        ]);

        // 2. (BARU) Sinkronkan Kategori
        // 'sync' akan: menghapus semua link lama, dan menambah semua link baru.
        // Jika 'category_ids' tidak dikirim, 'sync' akan menghapus semua link (default)
        if (isset($validatedData['category_ids'])) {
            $task->categories()->sync($validatedData['category_ids']);
        } else {
            // Jika user tidak mengirim 'category_ids',
            // kita anggap tidak ada perubahan (jangan hapus semua kategori)
            // Biarkan apa adanya.
        }

        return response()->json($task->load('categories'), 200);
    }

    /**
     * Menghapus tugas (dan cek kepemilikan)
     */
    public function destroy(Task $task)
    {
        // 1. Cek kepemilikan
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403); // 403 = Forbidden
        }

        // 2. Hapus task
        $task->delete();

        return response()->json(null, 204); // 204 = No Content
    }
}