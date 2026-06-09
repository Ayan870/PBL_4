<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "supervisor") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$supervisor_id = $_SESSION["user_id"];

// Get classes assigned to this supervisor
$classes_query = "SELECT class_id FROM class_supervisors WHERE supervisor_id = ?";
$stmt = mysqli_prepare($conn, $classes_query);
mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$class_ids = [];
while ($row = mysqli_fetch_assoc($res)) {
    $class_ids[] = $row['class_id'];
}

if (empty($class_ids)) {
    echo json_encode(["success" => true, "students" => []]);
    exit;
}

$ids_str = implode(',', $class_ids);

// Get students in these classes
// A student is in a class if they are in a group that is assigned to that class
$query = "
    SELECT DISTINCT u.id, u.name, u.roll_number, p.name as program_name
    FROM users u
    JOIN group_members gm ON u.id = gm.student_id
    JOIN `groups` g ON gm.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    WHERE g.class_id IN ($ids_str) AND gm.invite_status = 'accepted'
    ORDER BY u.name ASC
";

$res_s = mysqli_query($conn, $query);
$students = [];
while ($s = mysqli_fetch_assoc($res_s)) {
    $students[] = $s;
}

echo json_encode([
    "success" => true,
    "students" => $students
]);

mysqli_close($conn);
?>
