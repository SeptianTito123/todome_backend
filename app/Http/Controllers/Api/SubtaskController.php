<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subtask = $task->subtasks()->create([
            'title' => $request->title,
            'is_completed' => false
        ]);

        return response()->json($subtask, 201);
    }

    public function update(Request $request, Subtask $subtask)
    {
        if ($subtask->task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subtask->update($request->only(['title', 'is_completed']));

        return response()->json($subtask);
    }

    public function destroy(Request $request, Subtask $subtask)
    {
        if ($subtask->task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subtask->delete();

        return response()->json(['message' => 'Subtask deleted']);
    }
}
