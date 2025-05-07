<?php
session_start();
header('Content-Type: application/json');

$response = [
    'hasGoogleUser' => false,
    'email' => null,
    'name' => null
];

if (isset($_SESSION['google_user'])) {
    $response['hasGoogleUser'] = true;
    $response['email'] = $_SESSION['google_user']['email'];
    $response['name'] = $_SESSION['google_user']['name'];
}

echo json_encode($response); 