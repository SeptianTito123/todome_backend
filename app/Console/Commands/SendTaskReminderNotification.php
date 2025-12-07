<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\FcmToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendTaskReminderNotification extends Command
{
    protected $signature = 'task:reminder';
    protected $description = 'Kirim notifikasi reminder sebelum deadline task';

    public function handle()
    {
        DB::transaction(function () {

            $now = Carbon::now();

            $reminders = [
                60 => 'reminded_60',
                30 => 'reminded_30',
                15 => 'reminded_15',
                5  => 'reminded_5',
            ];

            foreach ($reminders as $minute => $flagColumn) {

                $start = $now->copy()->addMinutes($minute)->subSeconds(30);
                $end   = $now->copy()->addMinutes($minute)->addSeconds(30);

                $tasks = Task::whereBetween('deadline', [$start, $end])
                    ->where('status_selesai', 0)
                    ->where($flagColumn, 0)
                    ->lockForUpdate()
                    ->get();

                if ($tasks->count() === 0) {
                    continue;
                }

                $this->info("⏰ Reminder H-$minute Menit: " . $tasks->count());

                $accessToken = FirebaseService::getAccessToken();
                $projectId   = env('FIREBASE_PROJECT_ID');

                foreach ($tasks as $task) {

                    $tokens = FcmToken::where('user_id', $task->user_id)
                        ->pluck('token');

                    foreach ($tokens as $token) {

                        $response = Http::withToken($accessToken)
                            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                "message" => [
                                    "token" => $token,

                                    "notification" => [
                                        "title" => "⏰ Reminder Tugas",
                                        "body"  => "H-$minute menit: {$task->judul}",
                                    ],

                                    "android" => [
                                        "priority" => "HIGH",
                                        "ttl" => "0s",
                                        "notification" => [
                                            "channel_id" => "todome_fcm_alerts",
                                            "sound" => "default",
                                        ],
                                    ],

                                    "data" => [
                                        "type" => "reminder",
                                        "task_id" => (string) $task->id,
                                        "minute" => (string) $minute,
                                        "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                                    ],
                                ]
                            ]);

                        $this->info("📨 [$minute] {$task->judul} → " . $response->status());
                    }

                    // ✅ ANTI SPAM
                    $task->update([
                        $flagColumn => 1
                    ]);
                }
            }
        });

        $this->info("✅ Reminder check selesai");
    }
}
