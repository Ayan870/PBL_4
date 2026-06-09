<?php
/**
 * API Helper for PROJECXIA
 * Standardizes API initialization and responses.
 */

// 1. Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Set common headers
header("Content-Type: application/json");
header("X-Content-Type-Options: nosniff");

// 3. Error reporting (disable for production, keep for now)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 4. Include DB connection
require_once __DIR__ . "/../config/db.php";

/**
 * Standardized API response
 */
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data"    => $data
    ]);
    exit;
}

/**
 * Ensure the user is logged in for API calls
 */
function apiRequireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, "Unauthorized: Please log in.");
    }
}

/**
 * Ensure the user has the required role for API calls
 */
function apiRequireRole($expectedRole) {
    apiRequireAuth();
    if ($_SESSION['user_role'] !== $expectedRole) {
        sendResponse(false, "Forbidden: You do not have permission to perform this action.");
    }
}

/**
 * Helper to get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents("php://input");
    return json_decode($input, true);
}
?>
