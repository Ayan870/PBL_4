<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = $_SESSION["user_dept_id"] ?? 0;
$dept_id = (int)$dept_id;

if ($dept_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Your manager account is not linked to a department. Please log out and back in, or ensure your account has a department_id assigned in the database."
    ]);
    exit;
}

$hasDeptCol = function_exists('pbl_has_column') ? pbl_has_column($conn, 'users', 'department_id') : true;

if ($dept_id && $hasDeptCol) {
    $query = "
        SELECT u.id, u.name, u.email, u.role, u.roll_number, p.name as program_name, d.name as department_name,
               (SELECT pr.status 
                FROM group_members gm 
                JOIN proposals pr ON gm.group_id = pr.group_id 
                WHERE gm.student_id = u.id AND gm.invite_status = 'accepted' 
                ORDER BY pr.id DESC LIMIT 1) as proposal_status
        FROM users u
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.department_id = ? AND u.role IN ('student', 'supervisor')
        ORDER BY u.role, u.name
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $dept_id);
} elseif ($dept_id && !$hasDeptCol) {
    $query = "
        SELECT u.id, u.name, u.email, u.role, u.roll_number, p.name as program_name, d.name as department_name,
               (SELECT pr.status 
                FROM group_members gm 
                JOIN proposals pr ON gm.group_id = pr.group_id 
                WHERE gm.student_id = u.id AND gm.invite_status = 'accepted' 
                ORDER BY pr.id DESC LIMIT 1) as proposal_status
        FROM users u
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN departments d ON p.department_id = d.id
        WHERE p.department_id = ? AND u.role IN ('student', 'supervisor')
        ORDER BY u.role, u.name
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $dept_id);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database schema missing users.department_id. Run the SQL patch to add it, then try again."
    ]);
    exit;
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

echo json_encode([
    "success" => true,
    "users"   => $users
]);

mysqli_close($conn);
?>
