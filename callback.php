<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    if (isset($_GET['code'])) {
        // Get the authorization code
        $code = $_GET['code'];

        // Exchange code for access token
        $token_url = 'https://oauth2.googleapis.com/token';
        $token_data = [
            'code' => $code,
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
        curl_setopt($ch, CURLOPT_POST, true);
        $token_response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro ao obter token: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $token_info = json_decode($token_response, true);

        if (isset($token_info['error'])) {
            throw new Exception('Erro do Google: ' . $token_info['error_description']);
        }

        if (!isset($token_info['access_token'])) {
            throw new Exception('Token de acesso não recebido');
        }

        // Get user info from Google
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($user_info_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token_info['access_token']
        ]);
        $user_info_response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro ao obter informações do usuário: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $user_info = json_decode($user_info_response, true);

        if (isset($user_info['error'])) {
            throw new Exception('Erro do Google: ' . $user_info['error']['message']);
        }

        if (!isset($user_info['email'])) {
            throw new Exception('Email do usuário não encontrado');
        }

        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user_info['email']]);
        
        if ($stmt->rowCount() > 0) {
            // User already exists, just set session
            $_SESSION['google_user'] = [
                'email' => $user_info['email'],
                'name' => $user_info['name'] ?? '',
                'picture' => $user_info['picture'] ?? ''
            ];
        } else {
            // Get the selected interest from session
            $interest = isset($_SESSION['selected_interest']) ? $_SESSION['selected_interest'] : null;
            
            if (!$interest) {
                throw new Exception('Interesse não selecionado');
            }

            // Insert new user
            $stmt = $conn->prepare("
                INSERT INTO users (email, name, google_id, profile_picture, interest, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user_info['email'],
                $user_info['name'] ?? '',
                $user_info['id'] ?? null,
                $user_info['picture'] ?? null,
                $interest
            ]);

            // Set session
            $_SESSION['google_user'] = [
                'email' => $user_info['email'],
                'name' => $user_info['name'] ?? '',
                'picture' => $user_info['picture'] ?? ''
            ];
        }

        // Clear the selected interest from session
        unset($_SESSION['selected_interest']);

        // Redirect back to main page
        header('Location: index.php?google_auth=success');
        exit;
    }

    // If we get here, something went wrong
    throw new Exception('Código de autorização não recebido');

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: index.php?error=1&message=' . urlencode($e->getMessage()));
    exit;
} 