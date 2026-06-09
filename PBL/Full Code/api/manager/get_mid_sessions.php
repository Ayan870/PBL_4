<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

$dept_id = $_SESSION['user_dept_id'] ?? 0;

$query = "
    SELECT s.*, sem.session, sem.year, u.name as evaluator_name 
    FROM mid_eval_sessions s
    JOIN semesters sem ON s.semester_id = sem.id
    JOIN users u ON s.evaluator_id = u.id
    WHERE s.department_id = ?
    ORDER BY s.eval_date DESC, s.start_time DESC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $dept_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$sessions = [];
while ($row = mysqli_fetch_assoc($res)) {
    $sessions[] = $row;
}

echo json_encode(['success' => true, 'sessions' => $sessions]);
