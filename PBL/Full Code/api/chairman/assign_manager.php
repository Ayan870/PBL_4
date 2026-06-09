<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "chairman") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = (int)($data['user_id'] ?? 0);
$dept_id = (int)($data['department_id'] ?? 0);

if (!$user_id || !$dept_id) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // 1. Demote current manager if any
    $demote_query = "UPDATE users SET role = 'supervisor' WHERE department_id = ? AND role = 'pbl_manager'";
    $stmt1 = mysqli_prepare($conn, $demote_query);
    mysqli_stmt_bind_param($stmt1, "i", $dept_id);
    mysqli_stmt_execute($stmt1);

    // 2. Promote new user
    $promote_query = "UPDATE users SET role = 'pbl_manager', department_id = ? WHERE id = ?";
    $stmt2 = mysqli_prepare($conn, $promote_query);
    mysqli_stmt_bind_param($stmt2, "ii", $dept_id, $user_id);
    mysqli_stmt_execute($stmt2);

    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Manager assigned successfully"]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

mysqli_close($conn);
?>
