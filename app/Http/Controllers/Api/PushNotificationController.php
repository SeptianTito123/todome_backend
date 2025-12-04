<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class PushNotificationController extends Controller
{
    public function sendTest(Request $request)
    {
        $user = User::first();

        if (!$user || !$user->fcm_token) {
            return response()->json([
                'status' => false,
                'message' => 'User atau FCM Token tidak ditemukan'
            ]);
        }

        $firebaseServerKey = env('FIREBASE_SERVER_KEY');

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $firebaseServerKey,
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => '🔥 Notifikasi ToDoMe',
                'body'  => 'Push Notifikasi dari Server Laravel berhasil!',
            ],
            'priority' => 'high'
        ]);

        return response()->json([
            'status' => true,
            'firebase_response' => $response->json()
        ]);
    }
}
