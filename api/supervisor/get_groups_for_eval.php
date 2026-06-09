<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "supervisor") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$supervisor_id = $_SESSION["user_id"];

$query = "
    SELECT DISTINCT g.id, g.name, p.name as program_name
    FROM `groups` g
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    JOIN class_supervisors cs ON c.id = cs.class_id
    LEFT JOIN mid_evaluations me ON g.id = me.group_id AND cs.pbl_subject_id = me.pbl_subject_id
    WHERE cs.supervisor_id = ? AND me.id IS NULL
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$groups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $groups[] = $row;
}

echo json_encode(["success" => true, "groups" => $groups]);
mysqli_close($conn);
?>
