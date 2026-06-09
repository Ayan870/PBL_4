<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "evaluator") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$evaluator_id = $_SESSION["user_id"];
$data = json_decode(file_get_contents("php://input"), true);

$group_id = intval($data['group_id'] ?? 0);
$marks = intval($data['marks'] ?? 0);
$feedback = mysqli_real_escape_string($conn, $data['feedback'] ?? '');
$eval_date = date('Y-m-d');

if (!$group_id || !$feedback) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// 1. Check if session is running
$session_check = mysqli_query($conn, "SELECT status FROM final_eval_sessions WHERE evaluator_id = $evaluator_id AND status = 'running'");
if (mysqli_num_rows($session_check) === 0) {
    echo json_encode(["success" => false, "message" => "Your evaluation session is closed or not found."]);
    exit;
}

// 2. Get pbl_subject_id for this group
$group_res = mysqli_query($conn, "SELECT pbl_subject_id FROM `groups` WHERE id = $group_id");
$group_data = mysqli_fetch_assoc($group_res);
$subject_id = $group_data['pbl_subject_id'] ?? 0;

if (!$subject_id) {
    echo json_encode(["success" => false, "message" => "Invalid group"]);
    exit;
}

// 3. Check if already evaluated
$check_eval = mysqli_query($conn, "SELECT id FROM final_evaluations WHERE group_id = $group_id AND pbl_subject_id = $subject_id");
if (mysqli_num_rows($check_eval) > 0) {
    echo json_encode(["success" => false, "message" => "This group has already been evaluated for the final term."]);
    exit;
}

// 4. Insert final evaluation
$sql = "INSERT INTO final_evaluations (group_id, pbl_subject_id, evaluator_id, marks_out_of_20, feedback, evaluation_date)
        VALUES ($group_id, $subject_id, $evaluator_id, $marks, '$feedback', '$eval_date')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "Final evaluation submitted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error submitting evaluation: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
