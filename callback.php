<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/send_mail.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    if (isset($_GET['code'])) {
        // Troca o código por um token de acesso
        $token = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'code' => $_GET['code'],
                    'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                    'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
                    'grant_type' => 'authorization_code'
                ])
            ]
        ]));

        $token = json_decode($token, true);

        if (isset($token['access_token'])) {
            // Obtém informações do usuário
            $userInfo = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
                'http' => [
                    'header' => "Authorization: Bearer " . $token['access_token']
                ]
            ]));

            $userInfo = json_decode($userInfo, true);

            if (isset($userInfo['email'])) {
                $db = Database::getInstance();
                $conn = $db->getConnection();

                // Verifica se o usuário já existe
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$userInfo['email']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Usuário já existe, apenas atualiza a sessão
                    $_SESSION['google_user'] = [
                        'email' => $userInfo['email'],
                        'name' => $userInfo['name']
                    ];
                } else {
                    // Verifica se tem interesse selecionado
                    if (!isset($_SESSION['selected_interest'])) {
                        throw new Exception('Por favor, selecione se você é um prestador ou cliente antes de continuar.');
                    }

                    // Novo usuário, insere no banco
                    $stmt = $conn->prepare("
                        INSERT INTO users (email, name, interest, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    
                    $result = $stmt->execute([
                        $userInfo['email'],
                        $userInfo['name'],
                        $_SESSION['selected_interest']
                    ]);

                    if ($result) {
                        // Enviar e-mails de confirmação
                        $emailsSent = sendRegistrationEmails(
                            $userInfo['email'],
                            $userInfo['name'],
                            $_SESSION['selected_interest']
                        );
                        
                        if (!$emailsSent) {
                            error_log('E-mails não foram enviados, mas o cadastro foi realizado com sucesso.');
                        }

                        // Limpa o interesse da sessão
                        unset($_SESSION['selected_interest']);
                        
                        // Salva na sessão
                        $_SESSION['google_user'] = [
                            'email' => $userInfo['email'],
                            'name' => $userInfo['name']
                        ];

                        // Redireciona de volta para a página principal com o parâmetro de sucesso
                        header('Location: index.php?google_success=1');
                        exit;
                    } else {
                        throw new Exception('Erro ao salvar dados do usuário.');
                    }
                }
            }
        }
    }
    
    // Se chegou aqui, algo deu errado
    header('Location: index.php?error=1');
    exit;
    
} catch (Exception $e) {
    error_log("Google Auth Error: " . $e->getMessage());
    header('Location: index.php?error=1');
    exit;
} 