<?php
require_once 'config/database.php';
header('Content-Type: application/json');

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get and sanitize form data
        $email = sanitize($_POST['email']);
        $name = isset($_POST['nome']) ? sanitize($_POST['nome']) : null;
        $interest = sanitize($_POST['interest']); // 'provider' or 'client'
        $google_id = isset($_POST['google_id']) ? sanitize($_POST['google_id']) : null;
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Este e-mail já está cadastrado.']);
            exit;
        }
        
        // Insert new user
        $stmt = $db->prepare("
            INSERT INTO users (email, name, interest_type, google_id, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$email, $name, $interest, $google_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Pré-cadastro realizado com sucesso!']);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao processar o cadastro.']);
    }
} 