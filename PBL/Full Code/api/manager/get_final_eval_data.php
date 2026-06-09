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
    SELECT fe.*, g.name as group_name, ps.title as subject_title, u.name as evaluator_name
    FROM final_evaluations fe
    JOIN `groups` g ON fe.group_id = g.id
    JOIN pbl_subjects ps ON fe.pbl_subject_id = ps.id
    JOIN programs p ON ps.program_id = p.id
    JOIN users u ON fe.evaluator_id = u.id
    WHERE p.department_id = $dept_id
    ORDER BY fe.created_at DESC
";

$res = mysqli_query($conn, $query);
$evaluations = [];
while ($row = mysqli_fetch_assoc($res)) {
    $evaluations[] = $row;
}

echo json_encode(["success" => true, "evaluations" => $evaluations]);
mysqli_close($conn);
?>
