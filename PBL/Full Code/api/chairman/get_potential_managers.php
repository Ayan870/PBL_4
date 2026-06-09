<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "chairman") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = (int)($_GET['dept_id'] ?? 0);

// Get potential managers: Users in this department who are NOT students and NOT already managers in other departments
// Actually, usually supervisors are promoted to managers.
$query = "
    SELECT id, name, email, role, roll_number
    FROM users
    WHERE department_id = ? AND role != 'student' AND role != 'chairman' AND role != 'evaluator'
    ORDER BY name ASC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $dept_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($res)) {
    $users[] = $row;
}

echo json_encode([
    "success" => true,
    "users" => $users
]);

mysqli_close($conn);
?>
