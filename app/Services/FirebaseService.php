<?php

namespace App\Services;

use Google\Client;

class FirebaseService
{
    public static function getAccessToken()
    {
        $client = new Client();
        $client->setAuthConfig(env('FIREBASE_CREDENTIALS'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();

        $token = $client->getAccessToken();

        return $token['access_token'];
    }
}
