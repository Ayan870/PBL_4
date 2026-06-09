<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = $_SESSION["user_dept_id"] ?? 0;

$query = "
    SELECT pr.*, g.name as group_name, p.name as program_name, d.name as department_name, 
           u.name as supervisor_name, CONCAT(p.code, s.number) as class_name
    FROM proposals pr
    JOIN `groups` g ON pr.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN semesters s ON c.semester_id = s.id
    JOIN programs p ON c.program_id = p.id
    JOIN departments d ON p.department_id = d.id
    LEFT JOIN users u ON pr.supervisor_id = u.id
    WHERE d.id = ?
    ORDER BY pr.submitted_at DESC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $dept_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$proposals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proposals[] = $row;
}

echo json_encode([
    "success"   => true,
    "proposals" => $proposals
]);

mysqli_close($conn);
?>
