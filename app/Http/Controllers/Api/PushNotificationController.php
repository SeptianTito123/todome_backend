<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;

class PushNotificationController extends Controller
{
    public function sendTest(Request $request)
    {
        $tokens = FcmToken::pluck('token');

        if ($tokens->count() == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada FCM token tersimpan'
            ]);
        }

        $firebaseServerKey = env('FIREBASE_SERVER_KEY');

        foreach ($tokens as $token) {
            Http::withHeaders([
                'Authorization' => 'key=' . $firebaseServerKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => '🔥 Push Test ToDoMe',
                    'body'  => 'Push notifikasi berhasil dikirim!',
                ],
                'priority' => 'high'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Push test berhasil dikirim ke semua device'
        ]);
    }
}
