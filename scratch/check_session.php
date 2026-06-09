<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'user_id' => $_SESSION['user_id'] ?? 'MISSING',
    'user_role' => $_SESSION['user_role'] ?? 'MISSING',
    'user_name' => $_SESSION['user_name'] ?? 'MISSING',
    'session_id' => session_id()
]);
