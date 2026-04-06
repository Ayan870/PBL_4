<?php
// ===== Authentication Guard =====
// Include this at the top of every protected PHP page

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ' . str_repeat('../', $depth ?? 2) . 'index.html');
    exit;
}

// Set role for use in pages
$currentUser = $_SESSION;
$userRole    = $_SESSION['role'];
