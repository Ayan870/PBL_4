<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "evaluator") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$evaluator_id = $_SESSION["user_id"];

$query = "
    SELECT fe.*, g.name as group_name, ps.title as subject_title
    FROM final_evaluations fe
    JOIN `groups` g ON fe.group_id = g.id
    JOIN pbl_subjects ps ON fe.pbl_subject_id = ps.id
    WHERE fe.evaluator_id = $evaluator_id
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
