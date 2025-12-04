<?php

use Illuminate\Support\Facades\Http;

function getFirebaseAccessToken()
{
    $jsonKey = json_decode(file_get_contents(env('FIREBASE_CREDENTIALS')), true);

    $jwtHeader = base64_encode(json_encode([
        "alg" => "RS256",
        "typ" => "JWT"
    ]));

    $now = time();
    $jwtClaim = base64_encode(json_encode([
        "iss"   => $jsonKey['client_email'],
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud"   => "https://oauth2.googleapis.com/token",
        "iat"   => $now,
        "exp"   => $now + 3600,
    ]));

    openssl_sign("$jwtHeader.$jwtClaim", $signature, $jsonKey["private_key"], "SHA256");
    $jwtAssertion = "$jwtHeader.$jwtClaim." . base64_encode($signature);

    $response = Http::asForm()->post("https://oauth2.googleapis.com/token", [
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion"  => $jwtAssertion,
    ]);

    return $response['access_token'];
}

function sendFirebaseNotification($token, $title, $body)
{
    $accessToken = getFirebaseAccessToken();

    $url = "https://fcm.googleapis.com/v1/projects/" . env('FIREBASE_PROJECT_ID') . "/messages:send";

    $payload = [
        "message" => [
            "token" => $token,
            "notification" => [
                "title" => $title,
                "body"  => $body,
            ],
            "android" => [
                "priority" => "high"
            ]
        ]
    ];

    return Http::withToken($accessToken)
        ->post($url, $payload)
        ->json();
}
