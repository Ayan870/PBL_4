<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = $_SESSION["user_dept_id"] ?? 0;

// 1. Get Programs and Classes in this Dept
$programs = [];
$prog_res = mysqli_query($conn, "SELECT id, name FROM programs WHERE department_id = $dept_id");
while ($p = mysqli_fetch_assoc($prog_res)) {
    $prog_id = $p['id'];
    $classes = [];
    $class_res = mysqli_query($conn, "
        SELECT c.id, s.number as semester_number, s.session, c.section 
        FROM classes c 
        JOIN semesters s ON c.semester_id = s.id 
        WHERE c.program_id = $prog_id
    ");
    while ($c = mysqli_fetch_assoc($class_res)) {
        $classes[] = $c;
    }
    $p['classes'] = $classes;
    
    // Get subjects for this program
    $subjects = [];
    $sub_res = mysqli_query($conn, "SELECT id, title, semester_id FROM pbl_subjects WHERE program_id = $prog_id");
    while ($s = mysqli_fetch_assoc($sub_res)) {
        $subjects[] = $s;
    }
    $p['subjects'] = $subjects;
    
    $programs[] = $p;
}

// 2. Get Supervisors in this Dept
$supervisors = [];
// Assuming supervisors have department_id in users table or via program_id
$sup_query = "SELECT id, name FROM users WHERE role = 'supervisor' AND department_id = $dept_id";
$sup_res = mysqli_query($conn, $sup_query);
while ($s = mysqli_fetch_assoc($sup_res)) {
    $supervisors[] = $s;
}

// 3. Get Current Assignments
$assignments = [];
$assign_query = "
    SELECT cs.*, u.name as supervisor_name, p.name as program_name, s.number as semester_number, s.session, c.section, ps.title as subject_title
    FROM class_supervisors cs
    JOIN users u ON cs.supervisor_id = u.id
    JOIN classes c ON cs.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    JOIN semesters s ON c.semester_id = s.id
    JOIN pbl_subjects ps ON cs.pbl_subject_id = ps.id
    WHERE p.department_id = $dept_id
";
$assign_res = mysqli_query($conn, $assign_query);
while ($a = mysqli_fetch_assoc($assign_res)) {
    $assignments[] = $a;
}

// 4. Get All Available Subjects (Master List)
$all_subjects = [];
$master_res = mysqli_query($conn, "SHOW TABLES LIKE 'subjects_master'");
if (mysqli_num_rows($master_res) > 0) {
    $master_res = mysqli_query($conn, "SELECT id, title FROM subjects_master ORDER BY title ASC");
    while ($s = mysqli_fetch_assoc($master_res)) {
        $all_subjects[] = $s;
    }
} else {
    // Default subjects if table missing (fallback)
    $titles = ['OOP', 'PF', 'DSA', 'Database', 'Web dev', 'Mobile App dev', 'Machine Learning', 'Deep Learning', 'Robotic AI', 'Intro to AI'];
    foreach ($titles as $idx => $t) {
        $all_subjects[] = ['id' => $idx + 1, 'title' => $t];
    }
}

echo json_encode([
    "success" => true,
    "programs" => $programs,
    "supervisors" => $supervisors,
    "assignments" => $assignments,
    "all_subjects" => $all_subjects
]);

mysqli_close($conn);
?>
