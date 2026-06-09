<?php
require_once "../../helpers/auth_check.php";
checkRole('supervisor');
require_once "../../config/db.php";

$supervisor_id = $_SESSION['user_id'];
$dept_id = $_SESSION['user_dept_id'] ?? 0;

$now_date = date('Y-m-d');
$now_time = date('H:i:s');

$query = "
    SELECT * FROM mid_eval_sessions 
    WHERE evaluator_id = ? 
    AND eval_date = ? 
    AND start_time <= ? 
    AND end_time >= ?
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "isss", $supervisor_id, $now_date, $now_time, $now_time);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
    echo json_encode(['success' => true, 'session' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'No active evaluation session found for you at this time.']);
}
