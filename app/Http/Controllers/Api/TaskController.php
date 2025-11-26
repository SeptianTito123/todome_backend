<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Category;
use App\Models\Subtask;

class TaskController extends Controller
{
    // =========================
    // GET ALL TASKS
    // =========================
    public function index(Request $request)
    {
        $tasks = Task::with(['categories', 'subtasks'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks, 200);
    }

    // =========================
    // CREATE TASK
    // =========================
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
            'category_ids' => 'nullable|array',
            'subtasks' => 'nullable|array',
        ]);

        $task = Task::create([
            'user_id' => $request->user()->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'deadline' => $request->deadline,
            'status_selesai' => false,
            'is_starred' => false,
        ]);

        // === Attach Categories ===
        if ($request->category_ids) {
            $task->categories()->sync($request->category_ids);
        }

        // === Create Subtasks ===
        if ($request->subtasks) {
            foreach ($request->subtasks as $title) {
                Subtask::create([
                    'task_id' => $task->id,
                    'title' => $title,
                    'is_completed' => false,
                ]);
            }
        }

        return response()->json(
            Task::with(['categories', 'subtasks'])->find($task->id),
            201
        );
    }

    // =========================
    // UPDATE TASK
    // =========================
    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->only([
            'judul',
            'deskripsi',
            'deadline',
            'status_selesai',
            'is_starred'
        ]);

        // Convert boolean/string to integer compatible format
        if ($request->has('status_selesai')) {
            $data['status_selesai'] = $request->status_selesai ? 1 : 0;
        }

        if ($request->has('is_starred')) {
            $data['is_starred'] = $request->is_starred ? 1 : 0;
        }

        $task->update($data);

        // === Update Categories ===
        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids ?? []);
        }

        // NOTE:
        // Subtasks TIDAK di-update di sini (ada controller sendiri)

        return response()->json(
            Task::with(['categories', 'subtasks'])->find($task->id),
            200
        );
    }

    // =========================
    // DELETE TASK (Flutter Butuh 204)
    // =========================
    public function destroy(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete subtasks dulu
        $task->subtasks()->delete();

        // Detach categories
        $task->categories()->detach();

        $task->delete();

        return response(null, 204); // WAJIB 204 — SESUAI ApiService Flutter
    }
}
