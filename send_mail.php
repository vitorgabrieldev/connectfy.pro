<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailSender {
    private $mailer;
    private $adminEmail;

    public function __construct() {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
            $dotenv->load();
            
            $this->mailer = new PHPMailer(true);
            $this->adminEmail = $_ENV['ADMIN_EMAIL'];
            $this->configureMailer();
        } catch (Exception $e) {
            error_log('Erro ao inicializar MailSender: ' . $e->getMessage());
            throw $e;
        }
    }

    private function configureMailer() {
        try {
            // Server settings
            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $_ENV['SMTP_PORT'];
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
            $this->mailer->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        } catch (Exception $e) {
            error_log('Erro na configuração do mailer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendClientMail($email, $name, $interest) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->Subject = 'Bem-vindo à Connectfy!';
            
            // Carregar o template do cliente
            $template = file_get_contents(__DIR__ . '/template/mail_client.html');
            
            // Substituir as variáveis no template
            $template = str_replace('{name}', $name, $template);
            $template = str_replace('{interest_type}', $interest === 'provider' ? 'Prestador' : 'Cliente', $template);
            
            $this->mailer->Body = $template;
            $this->mailer->AltBody = strip_tags($template);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Erro ao enviar e-mail para o cliente: ' . $e->getMessage());
            return false;
        }
    }

    public function sendServerMail($email, $name, $interest) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($this->adminEmail);
            
            $this->mailer->Subject = 'Novo Cadastro na Connectfy';
            
            // Carregar o template do servidor
            $template = file_get_contents(__DIR__ . '/template/mail_server.html');
            
            // Substituir as variáveis no template
            $template = str_replace('{name}', $name, $template);
            $template = str_replace('{email}', $email, $template);
            $template = str_replace('{interest_type}', $interest === 'provider' ? 'Prestador' : 'Cliente', $template);
            $template = str_replace('{created_at}', date('d/m/Y H:i:s'), $template);
            
            $this->mailer->Body = $template;
            $this->mailer->AltBody = strip_tags($template);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Erro ao enviar e-mail para o servidor: ' . $e->getMessage());
            return false;
        }
    }
}

function sendRegistrationEmails($email, $name, $interest) {
    try {
        $mailSender = new MailSender();
        
        // Enviar e-mail para o cliente
        $clientMailSent = $mailSender->sendClientMail($email, $name, $interest);
        
        // Enviar e-mail para o administrador
        $serverMailSent = $mailSender->sendServerMail($email, $name, $interest);
        
        if (!$clientMailSent || !$serverMailSent) {
            error_log('Falha no envio de e-mails: ' . 
                     'Cliente: ' . ($clientMailSent ? 'OK' : 'Falhou') . ', ' .
                     'Servidor: ' . ($serverMailSent ? 'OK' : 'Falhou'));
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mails de cadastro: ' . $e->getMessage());
        return false;
    }
}
