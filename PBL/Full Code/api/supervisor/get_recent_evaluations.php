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
    SELECT e.*, u.name as student_name, u.roll_number
    FROM evaluations e
    JOIN users u ON e.student_id = u.id
    WHERE e.supervisor_id = ?
    ORDER BY e.created_at DESC
    LIMIT 10
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$evals = [];
while ($row = mysqli_fetch_assoc($res)) {
    $evals[] = $row;
}

echo json_encode([
    "success" => true,
    "evaluations" => $evals
]);

mysqli_close($conn);
?>
