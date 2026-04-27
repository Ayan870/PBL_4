<?php
// =============================================
// Login API - PBL Management System
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

$role          = $data["role"]     ?? "";
$password      = $data["password"] ?? "";
$password_hash = hash("sha256", $password);

if ($role === "manager") $role = "pbl_manager";

// -----------------------------------------------
// STUDENT - login with roll number
// -----------------------------------------------
if ($role === "student") {
    $roll = strtoupper(trim($data["roll_number"] ?? ""));

    if (empty($roll) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Roll number and password are required."]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "
        SELECT u.id, u.name, u.role, u.roll_number, u.password_hash,
               p.name AS program_name, p.code AS program_code
        FROM users u
        LEFT JOIN programs p ON u.program_id = p.id
        WHERE u.roll_number = ? AND u.role = 'student'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "s", $roll);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Roll number not found."]);
        exit;
    }

    if ($user["password_hash"] !== $password_hash) {
        echo json_encode(["success" => false, "message" => "Incorrect password."]);
        exit;
    }

    $_SESSION["user_id"]           = $user["id"];
    $_SESSION["user_name"]         = $user["name"];
    $_SESSION["user_role"]         = $user["role"];
    $_SESSION["user_roll"]         = $user["roll_number"];
    $_SESSION["user_program"]      = $user["program_name"];
    $_SESSION["user_program_code"] = $user["program_code"];

    echo json_encode([
        "success"  => true,
        "name"     => $user["name"],
        "role"     => $user["role"],
        "roll"     => $user["roll_number"],
        "redirect" => "/pbl-management-system/pages/student/dashboard.html"
    ]);
    exit;
}

// -----------------------------------------------
// SUPERVISOR / PBL MANAGER / EVALUATOR
// -----------------------------------------------
$email = strtolower(trim($data["email"] ?? ""));

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT id, name, role, email, password_hash
    FROM users
    WHERE email = ? AND role = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ss", $email, $role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Email not found for this role."]);
    exit;
}

if ($user["password_hash"] !== $password_hash) {
    echo json_encode(["success" => false, "message" => "Incorrect password."]);
    exit;
}

$redirects = [
    "supervisor"  => "/pbl-management-system/pages/supervisor/dashboard.html",
    "pbl_manager" => "/pbl-management-system/pages/manager/dashboard.html",
    "evaluator"   => "/pbl-management-system/pages/evaluator/final-evaluation.html"
];

$_SESSION["user_id"]    = $user["id"];
$_SESSION["user_name"]  = $user["name"];
$_SESSION["user_role"]  = $user["role"];
$_SESSION["user_email"] = $user["email"];

echo json_encode([
    "success"  => true,
    "name"     => $user["name"],
    "role"     => $user["role"],
    "email"    => $user["email"],
    "redirect" => $redirects[$user["role"]] ?? "/pbl-management-system/index.html"
]);

mysqli_close($conn);
