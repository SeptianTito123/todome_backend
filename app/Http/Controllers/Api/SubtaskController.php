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
        // Perbaikan: Konversi ke int
        if ((int)$task->user_id !== (int)$request->user()->id) {
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
        // Perbaikan: Konversi ke int
        if ((int)$subtask->task->user_id !== (int)$request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fix: Pastikan ambil boolean, bukan string "true"/"false"
        $data = $request->only(['title', 'is_completed']);
        if ($request->has('is_completed')) {
            $data['is_completed'] = filter_var($request->is_completed, FILTER_VALIDATE_BOOLEAN);
        }

        $subtask->update($data);

        return response()->json($subtask);
    }

    public function destroy(Request $request, Subtask $subtask)
    {
        // Perbaikan: Konversi ke int
        if ((int)$subtask->task->user_id !== (int)$request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subtask->delete();

        return response()->json(['message' => 'Subtask deleted']);
    }
}