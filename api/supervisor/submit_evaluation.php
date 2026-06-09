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

if (!$data || empty($data['group_id'])) {
    echo json_encode(["success" => false, "message" => "Missing required data"]);
    exit;
}

$group_id = $data['group_id'];
$tech_score = $data['tech_score'];
$progress = $data['progress_percent'] ?? 0;
$feedback = $data['feedback'];
$recommendations = $data['recommendations'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Get pbl_subject_id for this group and supervisor
    $find_query = "
        SELECT cs.pbl_subject_id
        FROM `groups` g
        JOIN class_supervisors cs ON g.class_id = cs.class_id
        WHERE g.id = ? AND cs.supervisor_id = ?
        LIMIT 1
    ";
    $stmt_f = mysqli_prepare($conn, $find_query);
    mysqli_stmt_bind_param($stmt_f, "ii", $group_id, $supervisor_id);
    mysqli_stmt_execute($stmt_f);
    $res_f = mysqli_stmt_get_result($stmt_f);
    $info = mysqli_fetch_assoc($res_f);

    if (!$info) {
        throw new Exception("You are not assigned to monitor this group.");
    }

    $subject_id = $info['pbl_subject_id'];
    
    // 2. Check if already evaluated (One-time only)
    $check_query = "SELECT id FROM mid_evaluations WHERE group_id = ? AND pbl_subject_id = ?";
    $stmt_c = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt_c, "ii", $group_id, $subject_id);
    mysqli_stmt_execute($stmt_c);
    $check_res = mysqli_stmt_get_result($stmt_c);

    if (mysqli_num_rows($check_res) > 0) {
        throw new Exception("This group has already been evaluated for the mid-term. Evaluations cannot be changed.");
    }

    // 3. Insert mid_evaluations (Group Summary)
    $insert_query = "INSERT INTO mid_evaluations (group_id, pbl_subject_id, evaluated_by, marks, progress_percent, feedback, evaluation_date) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt_i = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt_i, "iiidis", $group_id, $subject_id, $supervisor_id, $tech_score, $progress, $feedback);
    mysqli_stmt_execute($stmt_i);

    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Group evaluation submitted successfully"]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

mysqli_close($conn);
?>
