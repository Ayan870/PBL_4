<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "chairman") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// 1. Overall Stats
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student'"))['count'] ?? 0;
$total_supervisors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'supervisor'"))['count'] ?? 0;
$total_managers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'pbl_manager'"))['count'] ?? 0;
$total_proposals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals"))['count'] ?? 0;
$total_departments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM departments"))['count'] ?? 0;

// 2. Department Stats
$dept_query = "
    SELECT d.id, d.name, d.code,
           (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.role = 'student') as student_count,
           (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.role = 'supervisor') as supervisor_count,
           (SELECT u.name FROM users u WHERE u.department_id = d.id AND u.role = 'pbl_manager' LIMIT 1) as manager_name
    FROM departments d
";
$dept_res = mysqli_query($conn, $dept_query);
$departments = [];
while ($row = mysqli_fetch_assoc($dept_res)) {
    $departments[] = $row;
}

// 3. Program Progress (Aggregated)
$prog_query = "
    SELECT p.name, d.name as dept_name,
           (SELECT COUNT(*) FROM users u WHERE u.program_id = p.id AND u.role = 'student') as total_students,
           (SELECT COUNT(DISTINCT gm.student_id) 
            FROM group_members gm 
            JOIN `groups` g ON gm.group_id = g.id 
            JOIN proposals pr ON g.id = pr.group_id
            JOIN classes c ON g.class_id = c.id
            WHERE c.program_id = p.id AND gm.invite_status = 'accepted') as submitted_students
    FROM programs p
    JOIN departments d ON p.department_id = d.id
    ORDER BY d.name, p.name
";
$prog_res = mysqli_query($conn, $prog_query);
$programs = [];
while ($row = mysqli_fetch_assoc($prog_res)) {
    $programs[] = $row;
}

echo json_encode([
    "success" => true,
    "stats" => [
        "total_students" => $total_students,
        "total_supervisors" => $total_supervisors,
        "total_managers" => $total_managers,
        "total_proposals" => $total_proposals,
        "total_departments" => $total_departments
    ],
    "departments" => $departments,
    "programs" => $programs
]);

mysqli_close($conn);
?>
