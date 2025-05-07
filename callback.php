<?php
require_once 'config/database.php';
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

if (isset($_GET['code'])) {
    try {
        // Get token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // Get user info
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $google_id = $google_account_info->id;

        // Store in session for form submission
        session_start();
        $_SESSION['google_user'] = [
            'email' => $email,
            'name' => $name,
            'google_id' => $google_id
        ];

        // Redirect back to form
        header('Location: index.php#pre-register');
        exit;

    } catch (Exception $e) {
        header('Location: index.php?error=google_auth_failed');
        exit;
    }
} 