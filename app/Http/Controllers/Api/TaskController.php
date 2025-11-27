<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        return Task::where('user_id', $request->user()->id)
            ->with(['subtasks', 'categories'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul'          => 'required|string|max:255',
            'deskripsi'      => 'nullable|string',
            'deadline'       => 'nullable|date',
            'status_selesai' => 'nullable|boolean',
            'is_starred'     => 'nullable|boolean',
            'category_ids'   => 'array',          // <-- MANY TO MANY
            'category_ids.*' => 'exists:categories,id',
        ]);

        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

        // ----------- MANY TO MANY CATEGORY ----------
        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        return response()->json($task->load('categories', 'subtasks'), 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        return $task->load('categories', 'subtasks');
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        $validated = $request->validate([
            'judul'          => 'nullable|string|max:255',
            'deskripsi'      => 'nullable|string',
            'deadline'       => 'nullable|date',
            'status_selesai' => 'nullable|boolean',
            'is_starred'     => 'nullable|boolean',
            'category_ids'   => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $task->update($validated);

        // Update category pivot
        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        return response()->json([
            'message' => 'Task updated',
            'task' => $task->load('categories', 'subtasks')
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        // Hapus join pivot
        $task->categories()->detach();

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }

    /**
     * PERBAIKAN UTAMA DISINI:
     * Mengubah pengecekan strict (!==) menjadi casting integer ((int)... !== (int)...)
     * Ini mengatasi masalah jika Database mengembalikan ID sebagai String.
     */
    private function authorizeTask(Request $request, Task $task)
    {
        if ((int)$task->user_id !== (int)$request->user()->id) {
            // Debugging (Opsional, akan muncul di storage/logs/laravel.log jika error)
            // \Log::error("Auth Error: TaskUser: " . $task->user_id . " vs LoginUser: " . $request->user()->id);
            
            abort(403, 'Unauthorized access: Anda bukan pemilik tugas ini.');
        }
    }
}