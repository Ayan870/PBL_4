<?php
/**
 * Authentication & Authorization Helpers
 * Standardizes session checks and role-based access.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures the user is logged in. Redirects to index.php if not.
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        // Find the relative path to index.php
        // For simplicity and to match existing logic:
        header("Location: ../../index.php");
        exit;
    }
}

/**
 * Ensures the user has the required role.
 */
function requireRole($expectedRole) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== $expectedRole) {
        header("Location: ../../index.php");
        exit;
    }
}

/**
 * Legacy checkRole function for backward compatibility.
 */
function checkRole($expectedRole) {
    requireRole($expectedRole);
}

/**
 * Basic input sanitization.
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
