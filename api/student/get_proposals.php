<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION["user_id"];

// Get the group the student belongs to
$query_group = "SELECT group_id FROM group_members WHERE student_id = ? AND invite_status = 'accepted' LIMIT 1";
$stmt_g = mysqli_prepare($conn, $query_group);
mysqli_stmt_bind_param($stmt_g, "i", $user_id);
mysqli_stmt_execute($stmt_g);
$res_g = mysqli_stmt_get_result($stmt_g);
$group = mysqli_fetch_assoc($res_g);

if (!$group) {
    echo json_encode(["success" => true, "proposals" => []]);
    exit;
}

$group_id = $group["group_id"];

// Get proposals for this group
$query = "
    SELECT pr.*, 
           u.name as supervisor_name,
           p.name as program_name
    FROM proposals pr
    JOIN users u ON pr.supervisor_id = u.id
    JOIN `groups` g ON pr.group_id = g.id
    LEFT JOIN classes c ON g.class_id = c.id
    LEFT JOIN programs p ON c.program_id = p.id
    WHERE pr.group_id = ?
    ORDER BY pr.id DESC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $group_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$proposals = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Fetch attachments
    $query_a = "SELECT file_name, file_path FROM proposal_attachments WHERE proposal_id = ?";
    $stmt_a = mysqli_prepare($conn, $query_a);
    mysqli_stmt_bind_param($stmt_a, "i", $row["id"]);
    mysqli_stmt_execute($stmt_a);
    $res_a = mysqli_stmt_get_result($stmt_a);
    $attachments = [];
    while ($a = mysqli_fetch_assoc($res_a)) {
        $attachments[] = $a;
    }
    $row["attachments"] = $attachments;
    $proposals[] = $row;
}

echo json_encode([
    "success"   => true,
    "proposals" => $proposals
]);

mysqli_close($conn);
?>
