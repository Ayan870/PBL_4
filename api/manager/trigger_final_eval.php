<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$eval_name = mysqli_real_escape_string($conn, $data['name'] ?? '');
$eval_email = mysqli_real_escape_string($conn, $data['email'] ?? '');
$eval_password = $data['password'] ?? '';
$semester_id = intval($data['semester_id'] ?? 0);
$dept_id = $_SESSION['user_dept_id'] ?? 0;

if (!$eval_name || !$eval_email || !$eval_password || !$semester_id) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Check if email already exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$eval_email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(["success" => false, "message" => "Email already in use"]);
    exit;
}

// Check session limit (Max 4 active sessions per department)
$session_count_res = mysqli_query($conn, "SELECT COUNT(*) as active_count FROM final_eval_sessions WHERE department_id = $dept_id AND status = 'running'");
$session_count_data = mysqli_fetch_assoc($session_count_res);
if ($session_count_data['active_count'] >= 4) {
    echo json_encode(["success" => false, "message" => "Limit reached: You can have at most 4 active evaluation sessions at a time. Please close an existing session before triggering a new one."]);
    exit;
}

mysqli_begin_transaction($conn);

try {
    $password_hash = hash("sha256", $eval_password);
    $sql_user = "INSERT INTO users (name, email, password_hash, role, department_id, semester_id, is_temporary) 
                 VALUES ('$eval_name', '$eval_email', '$password_hash', 'evaluator', $dept_id, $semester_id, 1)";
    
    if (!mysqli_query($conn, $sql_user)) {
        throw new Exception("Error creating evaluator account: " . mysqli_error($conn));
    }
    
    $evaluator_id = mysqli_insert_id($conn);
    
    $sql_session = "INSERT INTO final_eval_sessions (department_id, semester_id, evaluator_id, status) 
                    VALUES ($dept_id, $semester_id, $evaluator_id, 'running')";
    
    if (!mysqli_query($conn, $sql_session)) {
        throw new Exception("Error creating evaluation session: " . mysqli_error($conn));
    }
    
    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Final evaluation triggered successfully"]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

mysqli_close($conn);
?>
