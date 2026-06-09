<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

$query = "DELETE FROM class_supervisors WHERE id = $id";

if (mysqli_query($conn, $query)) {
    echo json_encode(["success" => true, "message" => "Assignment removed"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

mysqli_close($conn);
?>
