<?php
session_start();
header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Usuário não encontrado'
];

if (isset($_SESSION['google_user'])) {
    $response = [
        'status' => 'success',
        'message' => 'Seu cadastro foi realizado com sucesso. Acompanhe seu e-mail para mais informações.',
        'email' => $_SESSION['google_user']['email'],
        'name' => $_SESSION['google_user']['name']
    ];
    
    // Limpa a sessão após enviar a resposta
    unset($_SESSION['google_user']);
}

echo json_encode($response); 