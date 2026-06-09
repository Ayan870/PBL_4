<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = $_SESSION['user_dept_id'] ?? 0;

$query = "
    SELECT fes.*, u.name as evaluator_name, u.email as evaluator_email, 
           s.number as semester_number, s.session, s.year
    FROM final_eval_sessions fes
    JOIN users u ON fes.evaluator_id = u.id
    JOIN semesters s ON fes.semester_id = s.id
    WHERE fes.department_id = $dept_id
    ORDER BY fes.created_at DESC
";

$res = mysqli_query($conn, $query);
$sessions = [];
while ($row = mysqli_fetch_assoc($res)) {
    $sessions[] = $row;
}

echo json_encode(["success" => true, "sessions" => $sessions]);
mysqli_close($conn);
?>
