<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$dept_id = $_SESSION['user_dept_id'] ?? 0;
$semester_id = $data['semester_id'] ?? 0;
$evaluator_id = $data['evaluator_id'] ?? 0;
$eval_date = $data['eval_date'] ?? '';
$start_time = $data['start_time'] ?? '';
$end_time = $data['end_time'] ?? '';

if (!$semester_id || !$evaluator_id || !$eval_date || !$start_time || !$end_time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$query = "INSERT INTO mid_eval_sessions (department_id, semester_id, evaluator_id, eval_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiisss", $dept_id, $semester_id, $evaluator_id, $eval_date, $start_time, $end_time);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Monitoring session scheduled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
