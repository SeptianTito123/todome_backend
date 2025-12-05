<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use App\Services\FirebaseService;

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
            'recurrence'     => 'nullable|string',
            'subtasks'       => 'nullable|array',
            'subtasks.*'     => 'string|max:255',
        ]);

        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        if ($request->has('subtasks') && is_array($request->subtasks)) {
            foreach ($request->subtasks as $subtaskTitle) {
                if (!empty($subtaskTitle)) {
                    $task->subtasks()->create([
                        'title' => $subtaskTitle,
                        'is_completed' => false,
                    ]);
                }
            }
        }

        // ✅ NOTIF TASK BARU
        $this->sendNotification(
            $request->user()->id,
            "Tugas Baru Dibuat ✅",
            $task->judul,
            (string) $task->id
        );

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
            'recurrence'     => 'nullable|string',
            'category_ids'   => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $statusSebelum = $task->status_selesai;

        $task->update($validated);

        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        // ✅ NOTIF JIKA TASK BARU SELESAI
        if ($statusSebelum == false && $task->status_selesai == true) {
            $this->sendNotification(
                $request->user()->id,
                "Tugas Selesai 🎉",
                "Tugas '{$task->judul}' telah diselesaikan",
                (string) $task->id
            );
        }

        return response()->json($task->load('categories', 'subtasks'));
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        $judulTask = $task->judul;

        $task->categories()->detach();
        $task->subtasks()->delete();
        $task->delete();

        // ✅ NOTIF TASK DIHAPUS
        $this->sendNotification(
            $request->user()->id,
            "Tugas Dihapus ❌",
            "Tugas '{$judulTask}' berhasil dihapus",
            null
        );

        return response()->json(['message' => 'Task deleted']);
    }

    // ==========================================
    // ✅ FUNCTION KIRIM NOTIF FCM v1 (REUSABLE)
    // ==========================================
    private function sendNotification($userId, $title, $body, $taskId = null)
    {
        $tokens = FcmToken::where('user_id', $userId)->pluck('token');

        if ($tokens->count() == 0) return;

        $accessToken = FirebaseService::getAccessToken();
        $projectId   = env('FIREBASE_PROJECT_ID');

        foreach ($tokens as $token) {
            Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    "message" => [
                        "token" => $token,
                        "notification" => [
                            "title" => $title,
                            "body"  => $body,
                        ],
                        "data" => [
                            "task_id" => $taskId
                        ]
                    ]
                ]);
        }
    }

    private function authorizeTask(Request $request, Task $task)
    {
        if ((int)$task->user_id !== (int)$request->user()->id) {
            abort(403, 'Unauthorized access: Anda bukan pemilik tugas ini.');
        }
    }
}
