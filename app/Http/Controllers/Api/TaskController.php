<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\FcmToken;


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
        // 1. Validasi Input
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

        // 2. Buat Task Utama
        $task = Task::create($validated);

        // 3. Simpan Kategori (Many-to-Many)
        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        // 4. Simpan Subtasks (One-to-Many)
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

        $fcmToken = \App\Models\FcmToken::where('user_id', $request->user()->id)->value('token');

        if ($fcmToken) {
            $serverKey = env('FIREBASE_SERVER_KEY'); // dari Firebase Cloud Messaging

            $payload = [
                "to" => $fcmToken,
                "notification" => [
                    "title" => "Tugas Baru Dibuat ✅",
                    "body"  => $task->judul,
                    "sound" => "default"
                ],
                "data" => [
                    "task_id" => $task->id
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: key=$serverKey",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            curl_exec($ch);
            curl_close($ch);
        }
        // 6. Kembalikan data lengkap dengan relasi
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

        $task->update($validated);

        if ($request->has('category_ids')) {
            $task->categories()->sync($request->category_ids);
        }

        return response()->json($task->load('categories', 'subtasks'));
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        // Hapus relasi pivot kategori
        $task->categories()->detach();
        
        // Subtask akan otomatis terhapus jika di database diset ON DELETE CASCADE
        // Tapi untuk aman, kita bisa hapus manual juga (opsional)
        $task->subtasks()->delete();

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