<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$res = mysqli_query($conn, "SELECT id, number, session, year FROM semesters ORDER BY year DESC, session DESC");
$semesters = [];
while ($row = mysqli_fetch_assoc($res)) {
    $semesters[] = $row;
}

echo json_encode(["success" => true, "semesters" => $semesters]);
mysqli_close($conn);
?>
