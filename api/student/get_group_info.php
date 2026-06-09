<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION["user_id"];
file_put_contents("../../scratch/get_group_debug.txt", "UserID: $user_id - Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// 1. Check if user is already in a group
$query = "
    SELECT g.*, ps.title as subject_title, u.name as leader_name
    FROM group_members gm
    JOIN `groups` g ON gm.group_id = g.id
    JOIN pbl_subjects ps ON g.pbl_subject_id = ps.id
    JOIN users u ON g.created_by = u.id
    WHERE gm.student_id = ? AND gm.invite_status = 'accepted'
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$group = mysqli_fetch_assoc($res);

if ($group) {
    // Get members
    $query_m = "
        SELECT u.name, u.roll_number, gm.role, gm.invite_status
        FROM group_members gm
        JOIN users u ON gm.student_id = u.id
        WHERE gm.group_id = ?
    ";
    $stmt_m = mysqli_prepare($conn, $query_m);
    mysqli_stmt_bind_param($stmt_m, "i", $group["id"]);
    mysqli_stmt_execute($stmt_m);
    $res_m = mysqli_stmt_get_result($stmt_m);
    $members = [];
    while ($m = mysqli_fetch_assoc($res_m)) {
        $members[] = $m;
    }
    $group["members"] = $members;
}

// 2. Get incoming invites
$invites = [];
$query_i = "
    SELECT g.id as group_id, g.name as group_name, u.name as from_name, u.roll_number as from_roll
    FROM group_members gm
    JOIN `groups` g ON gm.group_id = g.id
    JOIN users u ON g.created_by = u.id
    WHERE gm.student_id = ? AND gm.invite_status = 'pending'
";
$stmt_i = mysqli_prepare($conn, $query_i);
mysqli_stmt_bind_param($stmt_i, "i", $user_id);
mysqli_stmt_execute($stmt_i);
$res_i = mysqli_stmt_get_result($stmt_i);
while ($i = mysqli_fetch_assoc($res_i)) {
    $invites[] = $i;
}

echo json_encode([
    "success" => true,
    "group"   => $group,
    "invites" => $invites
]);

mysqli_close($conn);
?>
