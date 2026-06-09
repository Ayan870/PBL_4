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
    SELECT fes.*, s.session, s.year, s.number as semester_number
    FROM final_eval_sessions fes
    JOIN semesters s ON fes.semester_id = s.id
    WHERE fes.evaluator_id = $evaluator_id
    ORDER BY fes.created_at DESC
    LIMIT 1
";

$res = mysqli_query($conn, $query);
$session = mysqli_fetch_assoc($res);

if ($session) {
    echo json_encode(["success" => true, "session" => $session]);
} else {
    echo json_encode(["success" => false, "message" => "No session found"]);
}

mysqli_close($conn);
?>
