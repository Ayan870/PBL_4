<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$manager_id = $_SESSION["user_id"];
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['class_id']) || empty($data['supervisor_id']) || empty($data['subject_id'])) {
    echo json_encode(["success" => false, "message" => "Missing required data"]);
    exit;
}

$class_id = $data['class_id'];
$supervisor_id = $data['supervisor_id'];
$subject_id = $data['subject_id'];

// Check if assignment already exists
$check = mysqli_query($conn, "SELECT id FROM class_supervisors WHERE class_id = $class_id AND pbl_subject_id = $subject_id");
if (mysqli_num_rows($check) > 0) {
    // Update
    $query = "UPDATE class_supervisors SET supervisor_id = ?, assigned_by = ? WHERE class_id = ? AND pbl_subject_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiii", $supervisor_id, $manager_id, $class_id, $subject_id);
} else {
    // Insert
    $query = "INSERT INTO class_supervisors (class_id, supervisor_id, pbl_subject_id, assigned_by) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiii", $class_id, $supervisor_id, $subject_id, $manager_id);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Assignment saved successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
