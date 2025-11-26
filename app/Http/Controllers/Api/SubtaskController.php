<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\Task; // ← WAJIB ADA

class SubtaskController extends Controller
{
    /**
     * Menampilkan semua subtask dalam task tertentu
     */
    public function index(Task $task)
    {
        return response()->json($task->subtasks);
    }

    /**
     * Membuat subtask baru untuk task tertentu
     */
    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $subtask = Subtask::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'is_completed' => false,
        ]);

        return response()->json($subtask, 201);
    }

    /**
     * Update status atau title subtask
     */
    public function update(Request $request, Subtask $subtask)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'is_completed' => 'nullable|boolean',
        ]);

        if (isset($validated['title'])) {
            $subtask->title = $validated['title'];
        }

        if (isset($validated['is_completed'])) {
            $subtask->is_completed = $validated['is_completed'];
        }

        $subtask->save();

        return response()->json($subtask);
    }

    /**
     * Menghapus subtask
     */
    public function destroy(Subtask $subtask)
    {
        $subtask->delete();

        return response()->json([
            'message' => 'Subtask berhasil dihapus'
        ], 204);
    }
}
