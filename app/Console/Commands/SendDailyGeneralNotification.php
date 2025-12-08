<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FcmToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;

class SendDailyGeneralNotification extends Command
{
    protected $signature = 'notif:daily-general';
    protected $description = 'Kirim notifikasi harian pagi & malam';

    public function handle()
    {
        $accessToken = FirebaseService::getAccessToken();
        $projectId   = env('FIREBASE_PROJECT_ID');

        $tokens = FcmToken::pluck('token');

        if ($tokens->count() === 0) {
            $this->info("❌ Tidak ada token.");
            return;
        }

        foreach ($tokens as $token) {
            Http::withToken($accessToken)->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    "message" => [
                        "token" => $token,

                        "notification" => [
                            "title" => "⏰ Pengingat Harian",
                            "body"  => "Jangan lupa cek tugas kamu hari ini ✅",
                        ],

                        "android" => [
                            "priority" => "HIGH",
                            "notification" => [
                                "channel_id" => "todome_fcm_alerts",
                                "sound" => "default",
                            ],
                        ],

                        "data" => [
                            "type" => "daily_general",
                        ],
                    ]
                ]
            );
        }

        $this->info("✅ Notif daily umum dikirim.");
    }
}
