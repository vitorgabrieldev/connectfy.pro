<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['interest'])) {
    $_SESSION['selected_interest'] = $_POST['interest'];
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Interest not provided']);
} 