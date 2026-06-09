<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$class_id = $data['class_id'] ?? null;
$subject_title = $data['subject_title'] ?? null;
$manager_id = $_SESSION['user_id'];

if (!$class_id || !$subject_title) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get program_id and semester_id from class_id
$class_query = "SELECT program_id, semester_id FROM classes WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $class_query);
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$class_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$class_data) {
    echo json_encode(['success' => false, 'message' => 'Class not found']);
    exit;
}

$program_id = $class_data['program_id'];
$semester_id = $class_data['semester_id'];

// Check if this subject already exists for this program/semester
$check_query = "SELECT id FROM pbl_subjects WHERE program_id = ? AND semester_id = ? AND title = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "iis", $program_id, $semester_id, $subject_title);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($existing) {
    echo json_encode(['success' => true, 'message' => 'Subject already assigned', 'subject_id' => $existing['id']]);
    exit;
}

// Insert new assignment
// Note: If we want only ONE subject per program/semester, we should delete others. 
// But the schema allows multiple. Let's assume we just add it to the pool for that class.
$insert_query = "INSERT INTO pbl_subjects (program_id, semester_id, title, assigned_by) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($stmt, "iisi", $program_id, $semester_id, $subject_title, $manager_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Subject assigned successfully', 'subject_id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to assign subject: ' . mysqli_error($conn)]);
}
