<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "supervisor") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$supervisor_id = $_SESSION["user_id"];
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data["flex_id"]) || empty($data["status"])) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$flex_id  = (int)$data["flex_id"];
$status   = strtolower($data["status"]); // 'accepted' or 'rejected'
$feedback = $data["feedback"] ?? "";

if ($status === 'approved') $status = 'accepted';

// Check if assigned to this supervisor
$check_query = "SELECT status FROM flex_submissions WHERE id = ? AND supervisor_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $flex_id, $supervisor_id);
mysqli_stmt_execute($stmt);
$flex = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$flex) {
    echo json_encode(["success" => false, "message" => "Flex submission not found or not assigned to you."]);
    exit;
}

$query_upd = "UPDATE flex_submissions SET status = ?, feedback = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query_upd);
mysqli_stmt_bind_param($stmt, "ssi", $status, $feedback, $flex_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Flex submission updated successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
