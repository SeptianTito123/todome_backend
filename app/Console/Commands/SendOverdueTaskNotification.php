<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\FcmToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendOverdueTaskNotification extends Command
{
    protected $signature = 'task:overdue';
    protected $description = 'Kirim notifikasi untuk task yang terlambat';

    public function handle()
    {
        DB::transaction(function () {

            $tasks = Task::whereNotNull('deadline')
                ->where('deadline', '<', Carbon::now())
                ->where('status_selesai', 0)
                ->where(function ($q) {
                    $q->whereNull('notified_overdue')
                      ->orWhere('notified_overdue', 0);
                })
                ->lockForUpdate()
                ->get();

            if ($tasks->count() === 0) {
                $this->info("✅ Tidak ada task overdue.");
                return;
            }

            $this->info("🔥 Jumlah task overdue: " . $tasks->count());

            $accessToken = FirebaseService::getAccessToken();
            $projectId = env('FIREBASE_PROJECT_ID');

            foreach ($tasks as $task) {

                $this->info("📌 Kirim notif untuk task: " . $task->judul);

                $tokens = FcmToken::where('user_id', $task->user_id)->pluck('token');

                foreach ($tokens as $token) {
                    $response = Http::withToken($accessToken)
                        ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            "message" => [
                                "token" => $token,
                                "notification" => [
                                    "title" => "⚠️ Tugas Terlambat!",
                                    "body"  => $task->judul,
                                ],
                                "data" => [
                                    "type" => "overdue",
                                    "task_id" => (string) $task->id,
                                    "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                                ],
                                "android" => [
                                    "priority" => "HIGH"
                                ]
                            ]
                        ]);

                    $this->info("📨 Response FCM: " . $response->status());
                }

                // ✅ KUNCI: UPDATE SETELAH BERHASIL KIRIM
                $task->update([
                    'notified_overdue' => 1
                ]);
            }

            $this->info("✅ Selesai kirim notif overdue.");
        });
    }
}
