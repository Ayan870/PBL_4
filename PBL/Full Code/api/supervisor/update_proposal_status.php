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

if (!$data || empty($data["proposal_id"]) || empty($data["status"])) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$proposal_id = $data["proposal_id"];
$status      = $data["status"]; // 'Approved' or 'Rejected'
$comment     = $data["comment"] ?? "";

// Check if this supervisor is assigned to this proposal and get current status
$check_query = "SELECT status FROM proposals WHERE id = ? AND supervisor_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $proposal_id, $supervisor_id);
mysqli_stmt_execute($stmt);
$proposal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$proposal) {
    echo json_encode(["success" => false, "message" => "Proposal not found or not assigned to you."]);
    exit;
}

if ($proposal['status'] === 'accepted') {
    echo json_encode(["success" => false, "message" => "This proposal has already been approved and cannot be modified."]);
    exit;
}

// Map status to DB enum if needed (DB uses lowercase)
$db_status = strtolower($status);
if ($db_status === 'approved') $db_status = 'accepted';

$query_upd = "
    UPDATE proposals 
    SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
    WHERE id = ?
";
$stmt = mysqli_prepare($conn, $query_upd);
mysqli_stmt_bind_param($stmt, "ssii", $db_status, $comment, $supervisor_id, $proposal_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Proposal " . $status]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
