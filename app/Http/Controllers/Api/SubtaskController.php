<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subtask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        // 1. Cek Kepemilikan (apakah user ini pemilik task induk?)
        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // 2. Validasi (hanya butuh judul)
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // 3. Buat subtask baru
        $subtask = $task->subtasks()->create($validatedData);

        return response()->json($subtask, 201); // 201 = Created
    }
    // --- Fungsi untuk MENCENTANG sub-tugas ---
    public function update(Request $request, Subtask $subtask)
    {
        // 1. Cek Kepemilikan (melalui task induknya)
        if ($subtask->task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // 2. Validasi (hanya boleh update 'is_completed')
        $validatedData = $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        // 3. Update
        $subtask->update($validatedData);
        return response()->json($subtask, 200);
    }

    // --- Fungsi untuk MENGHAPUS sub-tugas ---
    public function destroy(Subtask $subtask)
    {
        // 1. Cek Kepemilikan
        if ($subtask->task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // 2. Hapus
        $subtask->delete();
        return response()->json(null, 204); // 204 = No Content
    }
}