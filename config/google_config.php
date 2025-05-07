<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$googleConfig = [
    'clientId' => $_ENV['GOOGLE_CLIENT_ID'],
    'redirectUri' => $_ENV['GOOGLE_REDIRECT_URI']
];

header('Content-Type: application/json');
echo json_encode($googleConfig); 