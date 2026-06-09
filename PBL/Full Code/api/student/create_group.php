<?php
// =============================================
// Create Group API - PBL Management System
// =============================================

session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION["user_id"];

// 1. Check if student already in a group
$check_q = "SELECT id FROM group_members WHERE student_id = ? AND invite_status = 'accepted' LIMIT 1";
$stmt = mysqli_prepare($conn, $check_q);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    echo json_encode(["success" => false, "message" => "You are already in a group."]);
    exit;
}

// 2. Get student's class and available subject
$student_q = "
    SELECT u.program_id, u.semester_id, c.id as class_id 
    FROM users u
    JOIN classes c ON u.program_id = c.program_id AND u.semester_id = c.semester_id
    WHERE u.id = ?
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $student_q);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student_data) {
    echo json_encode(["success" => false, "message" => "No class assigned to your program/semester yet."]);
    exit;
}

$class_id = $student_data["class_id"];

// Get the PBL subject for this class
$subject_q = "SELECT id FROM pbl_subjects WHERE program_id = ? AND semester_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $subject_q);
mysqli_stmt_bind_param($stmt, "ii", $student_data["program_id"], $student_data["semester_id"]);
mysqli_stmt_execute($stmt);
$subject_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$subject_data) {
    echo json_encode(["success" => false, "message" => "No PBL subject assigned to your class yet."]);
    exit;
}

$subject_id = $subject_data["id"];

// 3. Create Group
$data = json_decode(file_get_contents("php://input"), true);
$group_name = trim($data["name"] ?? "");

if (empty($group_name)) {
    // Default name if none provided
    $res = mysqli_query($conn, "SELECT roll_number FROM users WHERE id = $user_id");
    $roll = mysqli_fetch_assoc($res)["roll_number"];
    $group_name = "Group " . substr($roll, -3);
}

mysqli_begin_transaction($conn);

try {
    $ins_g = "INSERT INTO `groups` (class_id, pbl_subject_id, name, created_by, status) VALUES (?, ?, ?, ?, 'forming')";
    $stmt = mysqli_prepare($conn, $ins_g);
    mysqli_stmt_bind_param($stmt, "iisi", $class_id, $subject_id, $group_name, $user_id);
    mysqli_stmt_execute($stmt);
    $group_id = mysqli_insert_id($conn);

    $ins_m = "INSERT INTO group_members (group_id, student_id, role, invite_status, joined_at) VALUES (?, ?, 'leader', 'accepted', CURRENT_TIMESTAMP)";
    $stmt = mysqli_prepare($conn, $ins_m);
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $user_id);
    mysqli_stmt_execute($stmt);

    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Group created successfully!", "group_id" => $group_id]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

mysqli_close($conn);
