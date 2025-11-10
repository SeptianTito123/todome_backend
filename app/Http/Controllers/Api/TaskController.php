<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task; // <-- TAMBAHKAN INI
use Illuminate\Http\Request; // <-- TAMBAHKAN INI

class TaskController extends Controller
{
    /**
     * Menampilkan SEMUA tugas (GET /api/tasks)
     */
    public function index()
    {
        // Ambil semua data dari model Task
        return Task::all();
    }

    /**
     * Menyimpan tugas BARU (POST /api/tasks)
     */
    public function store(Request $request)
    {
        // Validasi data yang masuk (minimal judul harus ada)
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date'
        ]);

        // Buat tugas baru di database
        $task = Task::create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'deadline' => $request->deadline,
            // status_selesai otomatis false (lihat migrasi)
        ]);

        // Kembalikan data tugas yang baru dibuat sebagai JSON
        return response()->json($task, 201); // 201 = Created
    }

    /**
     * Menampilkan SATU tugas (GET /api/tasks/{id})
     */
    public function show(Task $task)
    {
        // Laravel otomatis mencari Task berdasarkan ID ($task)
        return $task;
    }

    /**
     * Meng-UPDATE tugas (PUT /api/tasks/{id})
     */
    public function update(Request $request, Task $task)
    {
        $request->validate([
            'judul' => 'string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status_selesai' => 'boolean' // Untuk menandai selesai
        ]);

        // Update data task
        $task->update($request->all());

        return response()->json($task, 200); // 200 = OK
    }

    /**
     * Menghapus tugas (DELETE /api/tasks/{id})
     */
    public function destroy(Task $task)
    {
        $task->delete();

        // Kembalikan response kosong "no content"
        return response()->json(null, 204); // 204 = No Content
    }
}