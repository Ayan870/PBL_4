<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || empty($data['id'])) {
    echo json_encode(["success" => false, "message" => "Missing ID"]);
    exit;
}

$id = $data['id'];
$query = "DELETE FROM class_supervisors WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Assignment removed"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

mysqli_close($conn);
?>
