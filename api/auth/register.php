<?php
// =============================================
// Register API - PBL Management System
// =============================================

session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

$role       = $data["role"]        ?? "";
$first_name = trim($data["first_name"] ?? "");
$last_name  = trim($data["last_name"]  ?? "");
$email      = strtolower(trim($data["email"]    ?? ""));
$password   = $data["password"]    ?? "";
$program    = $data["program"]     ?? "";
$roll       = strtoupper(trim($data["roll_number"] ?? ""));
$full_name  = $first_name . " " . $last_name;

// map manager to pbl_manager
if ($role === "manager") $role = "pbl_manager";

// Basic checks
if (empty($first_name) || empty($last_name) || empty($password) || empty($role)) {
    echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters."]);
    exit;
}

$password_hash = hash("sha256", $password);

// -----------------------------------------------
// STUDENT
// -----------------------------------------------
if ($role === "student") {

    if (empty($roll)) {
        echo json_encode(["success" => false, "message" => "Roll number is required."]);
        exit;
    }

    if (!preg_match('/^[A-Z]{2}\d{2}-[A-Z]+-[A-Z]\d{2}-\d{3,4}$/', $roll)) {
        echo json_encode(["success" => false, "message" => "Invalid roll number format. Example: SU74-BSCSM-F24-005"]);
        exit;
    }

    if (empty($program)) {
        echo json_encode(["success" => false, "message" => "Please select a department and program."]);
        exit;
    }

    // Check roll number not already used
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE roll_number = ?");
    mysqli_stmt_bind_param($stmt, "s", $roll);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        echo json_encode(["success" => false, "message" => "This roll number is already registered."]);
        exit;
    }

    // Get program_id
    $stmt = mysqli_prepare($conn, "SELECT id FROM programs WHERE code = ?");
    mysqli_stmt_bind_param($stmt, "s", $program);
    mysqli_stmt_execute($stmt);
    $result      = mysqli_stmt_get_result($stmt);
    $program_row = mysqli_fetch_assoc($result);

    if (!$program_row) {
        echo json_encode(["success" => false, "message" => "Program not found. Please run main.sql in phpMyAdmin first."]);
        exit;
    }
    $program_id = $program_row["id"];

    // Get latest semester
    $result      = mysqli_query($conn, "SELECT id FROM semesters ORDER BY year DESC, id DESC LIMIT 1");
    $sem_row     = mysqli_fetch_assoc($result);
    $semester_id = $sem_row ? $sem_row["id"] : null;

    // Insert student
    $stmt = mysqli_prepare($conn, "
        INSERT INTO users (name, email, password_hash, role, roll_number, program_id, semester_id, is_temporary, must_change_password)
        VALUES (?, ?, ?, 'student', ?, ?, ?, 0, 0)
    ");
    mysqli_stmt_bind_param($stmt, "ssssis", $full_name, $email, $password_hash, $roll, $program_id, $semester_id);
    mysqli_stmt_execute($stmt);

    echo json_encode([
        "success"  => true,
        "message"  => "Account created! You can now log in.",
        "redirect" => "/pbl-management-system/index.html"
    ]);
    exit;
}

// -----------------------------------------------
// SUPERVISOR or PBL MANAGER
// -----------------------------------------------
if ($role === "supervisor" || $role === "pbl_manager") {

    if (empty($email)) {
        echo json_encode(["success" => false, "message" => "Email is required."]);
        exit;
    }

    // Check email not already used
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        echo json_encode(["success" => false, "message" => "This email is already registered."]);
        exit;
    }

    // Insert user
    $stmt = mysqli_prepare($conn, "
        INSERT INTO users (name, email, password_hash, role, roll_number, program_id, semester_id, is_temporary, must_change_password)
        VALUES (?, ?, ?, ?, NULL, NULL, NULL, 0, 0)
    ");
    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $password_hash, $role);
    mysqli_stmt_execute($stmt);

    $redirect = $role === "supervisor"
        ? "/pbl-management-system/pages/supervisor/dashboard.html"
        : "/pbl-management-system/pages/manager/dashboard.html";

    echo json_encode([
        "success"  => true,
        "message"  => "Account created successfully!",
        "redirect" => $redirect
    ]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid role."]);
mysqli_close($conn);
