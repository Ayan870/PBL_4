<?php
// =============================================
// Handle Invite API - PBL Management System
// =============================================

session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION["user_id"];
$data = json_decode(file_get_contents("php://input"), true);
$action = $data["action"] ?? "";

// -----------------------------------------------
// SEND INVITE
// -----------------------------------------------
if ($action === "invite") {
    $roll = trim($data["roll_number"] ?? "");
    if (empty($roll)) {
        echo json_encode(["success" => false, "message" => "Roll number is required."]);
        exit;
    }

    // Find student
    // Find student and check program/semester
    $q_target = "
        SELECT u.id, u.name, u.program_id, u.semester_id, p.code as program_code, s.number as semester_num
        FROM users u
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN semesters s ON u.semester_id = s.id
        WHERE u.roll_number = ? AND u.role = 'student'
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $q_target);
    mysqli_stmt_bind_param($stmt, "s", $roll);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$student) {
        echo json_encode(["success" => false, "message" => "Student with roll number '$roll' not found."]);
        exit;
    }
    $target_student_id = $student["id"];
    $target_name = $student["name"] ?? "this student";
    $target_prog_id = $student["program_id"];
    $target_sem_id = $student["semester_id"];

    if ($target_student_id == $user_id) {
        echo json_encode(["success" => false, "message" => "You cannot invite yourself (Found: $target_name)."]);
        exit;
    }

    // Get current user's program/semester
    $stmt_me = mysqli_prepare($conn, "SELECT program_id, semester_id FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_me, "i", $user_id);
    mysqli_stmt_execute($stmt_me);
    $me = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_me));

    if ($me["program_id"] != $target_prog_id || $me["semester_id"] != $target_sem_id) {
        $msg = "Invitation blocked: You can only invite students from your own program and semester.";
        echo json_encode(["success" => false, "message" => $msg]);
        exit;
    }

    // Get current user's group
    $stmt = mysqli_prepare($conn, "SELECT group_id FROM group_members WHERE student_id = ? AND role = 'leader' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $my_group = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$my_group) {
        echo json_encode(["success" => false, "message" => "You must create a group first."]);
        exit;
    }
    $group_id = $my_group["group_id"];

    // Check group size
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM group_members WHERE group_id = $group_id AND invite_status IN ('accepted', 'pending')");
    $count = mysqli_fetch_assoc($res)["count"];
    if ($count >= 3) {
        echo json_encode(["success" => false, "message" => "Group is full (max 3)."]);
        exit;
    }

    // Check if target is already in a group
    $stmt = mysqli_prepare($conn, "SELECT id FROM group_members WHERE student_id = ? AND invite_status = 'accepted' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $target_student_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        echo json_encode(["success" => false, "message" => "Student is already in a group."]);
        exit;
    }

    // Check if an invite already exists
    $stmt = mysqli_prepare($conn, "SELECT invite_status FROM group_members WHERE group_id = ? AND student_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $target_student_id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existing) {
        $status = $existing['invite_status'];
        if ($status === 'accepted') {
            echo json_encode(["success" => false, "message" => "Student is already in your group."]);
            exit;
        }
        if ($status === 'pending') {
            echo json_encode(["success" => false, "message" => "Invite is already pending."]);
            exit;
        }
        
        // If rejected, we allow re-inviting
        $upd = "UPDATE group_members SET invite_status = 'pending', role = 'member' WHERE group_id = ? AND student_id = ?";
        $stmt = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($stmt, "ii", $group_id, $target_student_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => true, "message" => "Invite re-sent!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to re-send invite."]);
        }
        exit;
    }

    // Send new invite
    $ins = "INSERT INTO group_members (group_id, student_id, role, invite_status) VALUES (?, ?, 'member', 'pending')";
    $stmt = mysqli_prepare($conn, $ins);
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $target_student_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Invite sent!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to send invite."]);
    }
    exit;
}

// -----------------------------------------------
// RESPOND TO INVITE
// -----------------------------------------------
if ($action === "respond") {
    $group_id = $data["group_id"] ?? 0;
    $response = $data["response"] ?? ""; // 'accept' or 'reject'

    if (!$group_id || !in_array($response, ["accept", "reject"])) {
        echo json_encode(["success" => false, "message" => "Invalid parameters."]);
        exit;
    }

    if ($response === "reject") {
        $stmt = mysqli_prepare($conn, "UPDATE group_members SET invite_status = 'rejected' WHERE group_id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $group_id, $user_id);
        mysqli_stmt_execute($stmt);
        echo json_encode(["success" => true, "message" => "Invite rejected."]);
        exit;
    }

    // Accept
    // Check if already in a group
    $stmt = mysqli_prepare($conn, "SELECT id FROM group_members WHERE student_id = ? AND invite_status = 'accepted' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        echo json_encode(["success" => false, "message" => "You are already in a group."]);
        exit;
    }

    // Check group capacity
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM group_members WHERE group_id = $group_id AND invite_status = 'accepted'");
    $count = mysqli_fetch_assoc($res)["count"];
    if ($count >= 3) {
        echo json_encode(["success" => false, "message" => "Group is now full."]);
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        // Accept this one
        $upd = "UPDATE group_members SET invite_status = 'accepted', joined_at = CURRENT_TIMESTAMP WHERE group_id = ? AND student_id = ?";
        $stmt = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($stmt, "ii", $group_id, $user_id);
        mysqli_stmt_execute($stmt);

        // Reject all other pending invites for this student
        $upd_others = "UPDATE group_members SET invite_status = 'rejected' WHERE student_id = ? AND invite_status = 'pending'";
        $stmt = mysqli_prepare($conn, $upd_others);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        echo json_encode(["success" => true, "message" => "Joined group successfully!"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["success" => false, "message" => "Database error."]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action."]);
mysqli_close($conn);
?>
