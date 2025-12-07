<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\FcmToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SendOverdueTaskNotification extends Command
{
    protected $signature = 'task:overdue';
    protected $description = 'Kirim notifikasi untuk task yang terlambat';

    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');

        $tasks = Task::whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->where('status_selesai', false)
            ->where('notified_overdue', false)
            ->get();

        if ($tasks->count() === 0) {
            return;
        }

        $accessToken = FirebaseService::getAccessToken();
        $projectId = env('FIREBASE_PROJECT_ID');

        foreach ($tasks as $task) {

            $tokens = FcmToken::where('user_id', $task->user_id)->pluck('token');

            foreach ($tokens as $token) {
                Http::withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        "message" => [
                            "token" => $token,

                            // ✅ INI KUNCI AGAR NOTIF MUNCUL WALAU APP MATI
                            "android" => [
                                "priority" => "high",
                            ],

                            "notification" => [
                                "title" => "⚠️ Tugas Terlambat!",
                                "body"  => $task->judul,
                            ],

                            "data" => [
                                "task_id" => (string) $task->id,
                                "type"    => "overdue"
                            ]
                        ]
                    ]);
            }

            // ✅ KUNCI ANTI-SPAM
            $task->update(['notified_overdue' => true]);
        }
    }
}
