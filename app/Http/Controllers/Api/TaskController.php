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
            'category_ids'   => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

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

        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        // PERBAIKAN DISINI:
        // Kembalikan object task langsung (tanpa bungkus "message")
        // agar Flutter bisa langsung membacanya tanpa error.
        return response()->json($task->load('categories', 'subtasks'));
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);
        $task->categories()->detach();
        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }

    private function authorizeTask(Request $request, Task $task)
    {
        if ((int)$task->user_id !== (int)$request->user()->id) {
            abort(403, 'Unauthorized access: Anda bukan pemilik tugas ini.');
        }
    }
}