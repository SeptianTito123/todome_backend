<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\FcmToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SendDailyOverdueSummary extends Command
{
    protected $signature = 'notif:daily-overdue';
    protected $description = 'Kirim summary task terlambat tiap pagi & malam';

    public function handle()
    {
        $overdueUsers = Task::whereNotNull('deadline')
            ->where('deadline', '<', Carbon::now())
            ->where('status_selesai', 0)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->get();

        if ($overdueUsers->isEmpty()) {
            $this->info("✅ Tidak ada task overdue.");
            return;
        }

        $accessToken = FirebaseService::getAccessToken();
        $projectId   = env('FIREBASE_PROJECT_ID');

        foreach ($overdueUsers as $user) {
            $tokens = FcmToken::where('user_id', $user->user_id)->pluck('token');

            foreach ($tokens as $token) {
                Http::withToken($accessToken)->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        "message" => [
                            "token" => $token,

                            "notification" => [
                                "title" => "⚠️ Tugas Terlambat",
                                "body"  => "Kamu punya {$user->total} tugas belum selesai!",
                            ],

                            "android" => [
                                "priority" => "HIGH",
                                "notification" => [
                                    "channel_id" => "todome_fcm_alerts",
                                    "sound" => "default",
                                ],
                            ],

                            "data" => [
                                "type" => "daily_overdue",
                            ],
                        ]
                    ]
                );
            }
        }

        $this->info("✅ Daily overdue summary terkirim.");
    }
}
