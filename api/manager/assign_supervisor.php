<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$class_id = intval($data['class_id'] ?? 0);
$supervisor_id = intval($data['supervisor_id'] ?? 0);
$subject_title = trim($data['subject_title'] ?? '');
$assigned_by = $_SESSION['user_id'];

if (!$class_id || !$supervisor_id || !$subject_title) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// 1. Get program_id and semester_id for the class
$class_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT program_id, semester_id FROM classes WHERE id = $class_id"));
if (!$class_info) {
    echo json_encode(["success" => false, "message" => "Class not found"]);
    exit;
}
$prog_id = $class_info['program_id'];
$sem_id = $class_info['semester_id'];

// 2. Ensure subject exists in pbl_subjects for this program/semester
$check_sub = mysqli_query($conn, "SELECT id FROM pbl_subjects WHERE program_id = $prog_id AND semester_id = $sem_id AND title = '" . mysqli_real_escape_string($conn, $subject_title) . "' LIMIT 1");
if (mysqli_num_rows($check_sub) > 0) {
    $subject_id = mysqli_fetch_assoc($check_sub)['id'];
} else {
    // Create new subject assignment
    $ins_sub = "INSERT INTO pbl_subjects (program_id, semester_id, title, assigned_by) VALUES ($prog_id, $sem_id, '" . mysqli_real_escape_string($conn, $subject_title) . "', $assigned_by)";
    if (mysqli_query($conn, $ins_sub)) {
        $subject_id = mysqli_insert_id($conn);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create subject: " . mysqli_error($conn)]);
        exit;
    }
}

// 3. Check current assignments count for this class and subject
$count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM class_supervisors WHERE class_id = $class_id AND pbl_subject_id = $subject_id");
$count_row = mysqli_fetch_assoc($count_res);
if ($count_row['total'] >= 2) {
    echo json_encode(["success" => false, "message" => "Maximum 2 supervisors allowed per class/subject."]);
    exit;
}

// 4. Check if already assigned to THIS class/subject
$check_res = mysqli_query($conn, "SELECT id FROM class_supervisors WHERE class_id = $class_id AND supervisor_id = $supervisor_id AND pbl_subject_id = $subject_id");
if (mysqli_num_rows($check_res) > 0) {
    echo json_encode(["success" => false, "message" => "This supervisor is already assigned to this class/subject."]);
    exit;
}

// 5. Enforce One-Class Limit: Check if supervisor is already assigned to ANY other class
$check_other_class = mysqli_query($conn, "SELECT c.section, p.name as program_name FROM class_supervisors cs JOIN classes c ON cs.class_id = c.id JOIN programs p ON c.program_id = p.id WHERE cs.supervisor_id = $supervisor_id AND cs.class_id != $class_id LIMIT 1");
if ($row = mysqli_fetch_assoc($check_other_class)) {
    echo json_encode(["success" => false, "message" => "This supervisor is already assigned to " . $row['program_name'] . " (" . $row['section'] . "). A supervisor can only be assigned to one class."]);
    exit;
}

$query = "INSERT INTO class_supervisors (class_id, supervisor_id, pbl_subject_id, assigned_by) VALUES ($class_id, $supervisor_id, $subject_id, $assigned_by)";

if (mysqli_query($conn, $query)) {
    echo json_encode(["success" => true, "message" => "Supervisor assigned successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
