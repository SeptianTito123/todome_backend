<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FcmToken;

class NotificationController extends Controller
{
    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        FcmToken::updateOrCreate(
            ['token' => $request->fcm_token],
            ['user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'FCM token berhasil disimpan'
        ]);
    }
}
