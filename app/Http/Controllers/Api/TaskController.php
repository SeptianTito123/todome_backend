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
        // Muat relasi 'categories' DAN 'subtasks' yang baru
        return $user->tasks() 
                    ->with(['categories', 'subtasks'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Menyimpan tugas BARU (dan melampirkan kategori)
     */
    public function store(Request $request)
    {
        // 1. Validasi (tambahkan 'subtasks' sebagai array opsional)
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'subtasks' => 'nullable|array', // <-- 1. Validasi sub-tugas
            'subtasks.*' => 'string|max:255', // <-- 2. Setiap item adalah string
        ]);

        // 2. Buat task UTAMA
        $user = Auth::user();
        $task = $user->tasks()->create([
            'judul' => $validatedData['judul'],
            'deskripsi' => $validatedData['deskripsi'] ?? null,
            'deadline' => $validatedData['deadline'] ?? null,
        ]);

        // 3. Lampirkan Kategori jika ada
        if (isset($validatedData['category_ids'])) {
            $task->categories()->attach($validatedData['category_ids']);
        }

        // 4. (BARU) Buat Sub-tugas jika ada
        if (isset($validatedData['subtasks'])) {
            foreach ($validatedData['subtasks'] as $subtaskTitle) {
                $task->subtasks()->create(['title' => $subtaskTitle]);
            }
        }

        // 5. Kembalikan task LENGKAP dengan relasinya
        return response()->json($task->load(['categories', 'subtasks']), 201);
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
        return $task->load(['categories', 'subtasks']);
    }

    /**
     * Meng-UPDATE tugas (dan kategorinya)
     */
    public function update(Request $request, Task $task)
    {
        // 1. Cek kepemilikan
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403); // 403 = Forbidden
        }

        // 2. Validasi data yang masuk
        //    (Kita perbarui ini)
        $validatedData = $request->validate([
            'judul' => 'string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status_selesai' => 'boolean',
            'is_starred' => 'boolean', // <-- 1. TAMBAHKAN VALIDASI INI
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        // 3. Update data task (pisahkan kategori)
        //    (Kita perbarui ini)
        $task->update([
            'judul' => $validatedData['judul'] ?? $task->judul,
            'deskripsi' => $validatedData['deskripsi'] ?? $task->deskripsi,
            'deadline' => $validatedData['deadline'] ?? $task->deadline,
            'status_selesai' => $validatedData['status_selesai'] ?? $task->status_selesai,
            'is_starred' => $validatedData['is_starred'] ?? $task->is_starred, // <-- 2. TAMBAHKAN LOGIKA UPDATE INI
        ]);

        // 4. (BARU) Sinkronkan Kategori
        // 'sync' akan: menghapus semua link lama, dan menambah semua link baru.
        if (isset($validatedData['category_ids'])) {
            $task->categories()->sync($validatedData['category_ids']);
        }
        // (Kita hapus 'else' agar tidak mengacaukan kategori jika tidak dikirim)

        return response()->json($task->load('categories'), 200); // 200 = OK
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