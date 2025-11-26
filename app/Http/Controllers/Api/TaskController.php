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
            ->with(['subtasks', 'category'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

        return response()->json($task, 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        return $task->load('subtasks', 'category');
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        $task->update($request->all());

        return response()->json([
            'message' => 'Task updated',
            'task' => $task
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }

    private function authorizeTask(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access');
        }
    }
}
