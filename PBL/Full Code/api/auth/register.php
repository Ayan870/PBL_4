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
$hasDeptCol = function_exists('pbl_has_column') ? pbl_has_column($conn, 'users', 'department_id') : true;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
file_put_contents("register_debug.log", date("[Y-m-d H:i:s] ") . "Received: " . json_encode($data) . "\n", FILE_APPEND);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

$role       = $data["role"]        ?? "";
file_put_contents("register_debug.log", "Role determined: " . $role . "\n", FILE_APPEND);
$first_name = trim($data["first_name"] ?? "");
$last_name  = trim($data["last_name"]  ?? "");
$email      = strtolower(trim($data["email"]    ?? ""));
$password   = $data["password"]    ?? "";
$program    = $data["program"]     ?? "";
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

    if (empty($program)) {
        echo json_encode(["success" => false, "message" => "Please select a department and program."]);
        exit;
    }

    // Get program_id, department_id, and program_code
    $stmt = mysqli_prepare($conn, "SELECT id, department_id, code FROM programs WHERE code = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database query error: " . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "s", $program);
    mysqli_stmt_execute($stmt);
    $result      = mysqli_stmt_get_result($stmt);
    $program_row = mysqli_fetch_assoc($result);

    if (!$program_row) {
        echo json_encode(["success" => false, "message" => "Program not found."]);
        exit;
    }
    $program_id    = $program_row["id"];
    $department_id = $program_row["department_id"];
    $program_code  = $program_row["code"];

    $semester_id = $data["semester_id"] ?? null;
    $enroll_year = $data["enrollment_year"] ?? date("Y");
    $enroll_sess = $data["enrollment_session"] ?? "Fall";

    $year_short = substr($enroll_year, -2); // '23'
    $sess_letter = strtoupper(substr($enroll_sess, 0, 1)); // 'F'

    // Auto-generate roll number
    $prefix = "SU74-" . $program_code . "-" . $sess_letter . $year_short . "-";
    
    // Find the max roll number with this prefix
    $stmt_max = mysqli_prepare($conn, "SELECT roll_number FROM users WHERE roll_number LIKE ? ORDER BY roll_number DESC LIMIT 1");
    $prefix_like = $prefix . "%";
    mysqli_stmt_bind_param($stmt_max, "s", $prefix_like);
    mysqli_stmt_execute($stmt_max);
    $res_max = mysqli_stmt_get_result($stmt_max);
    $row_max = mysqli_fetch_assoc($res_max);
    
    $next_num = 1;
    if ($row_max) {
        // Extract the last part
        $parts = explode("-", $row_max["roll_number"]);
        $last_part = end($parts);
        $next_num = intval($last_part) + 1;
    }
    $roll = $prefix . str_pad($next_num, 3, "0", STR_PAD_LEFT);

    // Insert student
    $insertCols = $hasDeptCol
        ? "(name, email, password_hash, role, roll_number, program_id, department_id, semester_id, is_temporary, must_change_password)"
        : "(name, email, password_hash, role, roll_number, program_id, semester_id, is_temporary, must_change_password)";
    $insertVals = $hasDeptCol
        ? "(?, ?, ?, 'student', ?, ?, ?, ?, 0, 0)"
        : "(?, ?, ?, 'student', ?, ?, ?, 0, 0)";

    $stmt = mysqli_prepare($conn, "
        INSERT INTO users $insertCols
        VALUES $insertVals
    ");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database query error: " . mysqli_error($conn)]);
        exit;
    }
    if ($hasDeptCol) {
        mysqli_stmt_bind_param($stmt, "ssssiii", $full_name, $email, $password_hash, $roll, $program_id, $department_id, $semester_id);
    } else {
        mysqli_stmt_bind_param($stmt, "ssssii", $full_name, $email, $password_hash, $roll, $program_id, $semester_id);
    }
    mysqli_stmt_execute($stmt);

    // --- AUTO-BOOTSTRAP CLASS AND SUBJECT ---
    // Ensure the class exists for this program/semester
    $check_c = mysqli_prepare($conn, "SELECT id FROM classes WHERE program_id = ? AND semester_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check_c, "ii", $program_id, $semester_id);
    mysqli_stmt_execute($check_c);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check_c))) {
        $ins_c = mysqli_prepare($conn, "INSERT INTO classes (program_id, semester_id, section) VALUES (?, ?, 'A')");
        mysqli_stmt_bind_param($ins_c, "ii", $program_id, $semester_id);
        mysqli_stmt_execute($ins_c);
    }

    // Ensure the PBL subject exists
    $check_s = mysqli_prepare($conn, "SELECT id FROM pbl_subjects WHERE program_id = ? AND semester_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check_s, "ii", $program_id, $semester_id);
    mysqli_stmt_execute($check_s);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check_s))) {
        $ins_s = mysqli_prepare($conn, "INSERT INTO pbl_subjects (program_id, semester_id, title) VALUES (?, ?, 'PBL Project')");
        mysqli_stmt_bind_param($ins_s, "ii", $program_id, $semester_id);
        mysqli_stmt_execute($ins_s);
    }
    // ----------------------------------------

    echo json_encode([
        "success"  => true,
        "message"  => "Account created! Your roll number is: " . $roll,
        "redirect" => "index.php"
    ]);
    exit;
}

// -----------------------------------------------
// SUPERVISOR or PBL MANAGER
// -----------------------------------------------
if ($role === "supervisor" || $role === "pbl_manager") {

    $dept_name = $data["department"] ?? "";
    $prog_code = $data["program"]    ?? "";

    if (empty($dept_name)) {
        echo json_encode(["success" => false, "message" => "Department is required."]);
        exit;
    }

    // Get department_id
    $stmt = mysqli_prepare($conn, "SELECT id FROM departments WHERE name = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database query error: " . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "s", $dept_name);
    mysqli_stmt_execute($stmt);
    $dept_res = mysqli_stmt_get_result($stmt);
    $dept_row = mysqli_fetch_assoc($dept_res);
    $department_id = $dept_row ? $dept_row["id"] : null;

    if (!$department_id) {
        echo json_encode(["success" => false, "message" => "Department not found."]);
        exit;
    }

    if ($role === "pbl_manager" && $hasDeptCol) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'pbl_manager' AND department_id = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Database query error: " . mysqli_error($conn)]);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "i", $department_id);
        mysqli_stmt_execute($stmt);
        $exists_res = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($exists_res)) {
            echo json_encode(["success" => false, "message" => "A PBL Manager already exists for this department."]);
            exit;
        }
    }

    $program_id = null;
    if ($role === "supervisor") {
        if (empty($prog_code)) {
            echo json_encode(["success" => false, "message" => "Program is required for supervisors."]);
            exit;
        }
        $stmt = mysqli_prepare($conn, "SELECT id FROM programs WHERE code = ?");
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Database query error: " . mysqli_error($conn)]);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "s", $prog_code);
        mysqli_stmt_execute($stmt);
        $prog_res = mysqli_stmt_get_result($stmt);
        $prog_row = mysqli_fetch_assoc($prog_res);
        $program_id = $prog_row ? $prog_row["id"] : null;

        if (!$program_id) {
            echo json_encode(["success" => false, "message" => "Program not found."]);
            exit;
        }
    }

    // Insert user
    $insertCols = $hasDeptCol
        ? "(name, email, password_hash, role, roll_number, program_id, department_id, semester_id, is_temporary, must_change_password)"
        : "(name, email, password_hash, role, roll_number, program_id, semester_id, is_temporary, must_change_password)";
    $insertVals = $hasDeptCol
        ? "(?, ?, ?, ?, NULL, ?, ?, NULL, 0, 0)"
        : "(?, ?, ?, ?, NULL, ?, NULL, 0, 0)";

    $stmt = mysqli_prepare($conn, "
        INSERT INTO users $insertCols
        VALUES $insertVals
    ");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database query error: " . mysqli_error($conn)]);
        exit;
    }
    if ($hasDeptCol) {
        mysqli_stmt_bind_param($stmt, "ssssii", $full_name, $email, $password_hash, $role, $program_id, $department_id);
    } else {
        mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $password_hash, $role, $program_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $redirects = [
            "supervisor"  => "pages/supervisor/dashboard.php",
            "pbl_manager" => "pages/manager/dashboard.php",
            "evaluator"   => "pages/evaluator/final-evaluation.php"
        ];

        echo json_encode([
            "success"  => true,
            "message"  => "Account created successfully!",
            "redirect" => $redirects[$role] ?? "index.php"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed: " . mysqli_stmt_error($stmt)]);
    }
    exit;
}

// -----------------------------------------------
// EVALUATOR
// -----------------------------------------------
if ($role === "evaluator") {
    $dept_name = $data["department"] ?? "";
    if (empty($dept_name)) {
        echo json_encode(["success" => false, "message" => "Department is required for evaluators."]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id FROM departments WHERE name = ?");
    mysqli_stmt_bind_param($stmt, "s", $dept_name);
    mysqli_stmt_execute($stmt);
    $dept_res = mysqli_stmt_get_result($stmt);
    $dept_row = mysqli_fetch_assoc($dept_res);
    $department_id = $dept_row ? $dept_row["id"] : null;

    if (!$department_id) {
        echo json_encode(["success" => false, "message" => "Department not found."]);
        exit;
    }

    $insertCols = $hasDeptCol
        ? "(name, email, password_hash, role, department_id, is_temporary, must_change_password)"
        : "(name, email, password_hash, role, is_temporary, must_change_password)";
    $insertVals = $hasDeptCol ? "(?, ?, ?, 'evaluator', ?, 0, 0)" : "(?, ?, ?, 'evaluator', 0, 0)";

    $stmt = mysqli_prepare($conn, "INSERT INTO users $insertCols VALUES $insertVals");
    if ($hasDeptCol) {
        mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $password_hash, $department_id);
    } else {
        mysqli_stmt_bind_param($stmt, "sss", $full_name, $email, $password_hash);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success"  => true,
            "message"  => "Evaluator account created!",
            "redirect" => "pages/evaluator/final-evaluation.php"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed: " . mysqli_stmt_error($stmt)]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid role selected."]);
mysqli_close($conn);
?>
