<?php
require_once 'config/database.php';
require_once 'send_mail.php';
header('Content-Type: application/json');

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log the received data
        error_log("Received POST data: " . print_r($_POST, true));

        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Get and sanitize form data
        $email = sanitize($_POST['email']);
        $name = isset($_POST['nome']) ? sanitize($_POST['nome']) : null;
        $interest = sanitize($_POST['interest']); // 'provider' or 'client'
        
        // Log sanitized data
        error_log("Sanitized data - Email: $email, Name: $name, Interest: $interest");
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Este e-mail já está cadastrado.']);
            exit;
        }
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (email, name, interest, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([$email, $name, $interest]);
        
        if ($result) {
            // Enviar e-mails de confirmação
            $emailsSent = sendRegistrationEmails($email, $name, $interest);
            
            if (!$emailsSent) {
                error_log('E-mails não foram enviados, mas o cadastro foi realizado com sucesso.');
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Pré-cadastro realizado com sucesso!']);
        } else {
            $error = $stmt->errorInfo();
            error_log("Database error: " . print_r($error, true));
            throw new PDOException("Erro ao inserir no banco de dados: " . $error[2]);
        }
        
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Erro ao processar o cadastro.',
            'debug' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Erro ao processar o cadastro.',
            'debug' => $e->getMessage()
        ]);
    }
} 