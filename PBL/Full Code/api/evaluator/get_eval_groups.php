<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "evaluator") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION["user_id"];
$dept_id = $_SESSION["user_dept_id"] ?? 0;

// Get the evaluator's assigned semester
$user_res = mysqli_query($conn, "SELECT semester_id FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_res);
$semester_id = $user_data['semester_id'] ?? 0;

if (!$semester_id) {
    echo json_encode(["success" => false, "message" => "No semester assigned to this evaluator"]);
    exit;
}

// Fetch groups in this department and semester
// Groups -> Classes -> Semester
// Groups -> PBL Subjects -> Program -> Department
$query = "
    SELECT g.id, g.name, ps.title as subject_title, p.name as program_name
    FROM `groups` g
    JOIN classes c ON g.class_id = c.id
    JOIN pbl_subjects ps ON g.pbl_subject_id = ps.id
    JOIN programs p ON ps.program_id = p.id
    LEFT JOIN final_evaluations fe ON g.id = fe.group_id AND ps.id = fe.pbl_subject_id
    WHERE c.semester_id = $semester_id AND p.department_id = $dept_id AND fe.id IS NULL
";

$res = mysqli_query($conn, $query);
$groups = [];
while ($row = mysqli_fetch_assoc($res)) {
    // Also get members for display
    $group_id = $row['id'];
    $mem_res = mysqli_query($conn, "
        SELECT u.name, u.roll_number 
        FROM group_members gm 
        JOIN users u ON gm.student_id = u.id 
        WHERE gm.group_id = $group_id AND gm.invite_status = 'accepted'
    ");
    $members = [];
    while ($m = mysqli_fetch_assoc($mem_res)) {
        $members[] = $m['name'] . " (" . $m['roll_number'] . ")";
    }
    $row['members_str'] = implode(", ", $members);
    $groups[] = $row;
}

echo json_encode(["success" => true, "groups" => $groups]);
mysqli_close($conn);
?>
