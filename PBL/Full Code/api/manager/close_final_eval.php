<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$session_id = intval($data['id'] ?? 0);
$dept_id = $_SESSION['user_dept_id'] ?? 0;

if (!$session_id) {
    echo json_encode(["success" => false, "message" => "Missing session ID"]);
    exit;
}

// Ensure the session belongs to the department
$check = mysqli_query($conn, "SELECT id FROM final_eval_sessions WHERE id = $session_id AND department_id = $dept_id");
if (mysqli_num_rows($check) === 0) {
    echo json_encode(["success" => false, "message" => "Session not found or access denied"]);
    exit;
}

$sql = "UPDATE final_eval_sessions SET status = 'closed' WHERE id = $session_id";
if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "Session closed successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error closing session: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
