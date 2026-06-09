<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = $_SESSION["user_dept_id"] ?? 0;

// Get only completed mid-term evaluations for this department
$query = "
    SELECT 
        me.marks, 
        me.progress_percent, 
        me.evaluation_date, 
        me.feedback, 
        g.name as group_name,
        p.name as program_name,
        c.section,
        ps.title as subject_title,
        u.name as supervisor_name
    FROM mid_evaluations me
    JOIN `groups` g ON me.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    JOIN pbl_subjects ps ON me.pbl_subject_id = ps.id
    JOIN users u ON me.evaluated_by = u.id
    WHERE p.department_id = $dept_id
    ORDER BY me.evaluation_date DESC
";

$res = mysqli_query($conn, $query);
$evaluations = [];

while ($row = mysqli_fetch_assoc($res)) {
    $row['status'] = 'Completed';
    $evaluations[] = $row;
}

echo json_encode([
    "success" => true,
    "evaluations" => $evaluations
]);

mysqli_close($conn);
?>
