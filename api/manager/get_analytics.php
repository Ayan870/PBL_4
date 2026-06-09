<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/db.php";

if (empty($_SESSION["user_id"]) || $_SESSION["user_role"] !== "pbl_manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$dept_id = $_SESSION['user_dept_id'] ?? 0;

// 1. Total Students
$students_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student' AND department_id = $dept_id");
$total_students = mysqli_fetch_assoc($students_res)['count'] ?? 0;

// 2. Graded Projects
$graded_res = mysqli_query($conn, "
    SELECT COUNT(DISTINCT fe.group_id) as count 
    FROM final_evaluations fe
    JOIN `groups` g ON fe.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    WHERE p.department_id = $dept_id
");
$graded_count = mysqli_fetch_assoc($graded_res)['count'] ?? 0;

// 3. Average Score
$avg_res = mysqli_query($conn, "
    SELECT AVG(fe.marks_out_of_20) as avg_score
    FROM final_evaluations fe
    JOIN `groups` g ON fe.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    WHERE p.department_id = $dept_id
");
$avg_score = mysqli_fetch_assoc($avg_res)['avg_score'] ?? 0;
$avg_percentage = round($avg_score * 5, 1); // out of 100

// 4. Passing % (Score >= 10/20)
$pass_res = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM final_evaluations fe
    JOIN `groups` g ON fe.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    WHERE p.department_id = $dept_id AND fe.marks_out_of_20 >= 10
");
$pass_count = mysqli_fetch_assoc($pass_res)['count'] ?? 0;
$pass_percentage = $graded_count > 0 ? round(($pass_count / $graded_count) * 100, 1) : 0;

// 5. Program Comparison (Avg score per program)
$programs = [];
$prog_res = mysqli_query($conn, "
    SELECT p.name, p.code, AVG(fe.marks_out_of_20) as avg_marks
    FROM programs p
    LEFT JOIN classes c ON c.program_id = p.id
    LEFT JOIN `groups` g ON g.class_id = c.id
    LEFT JOIN final_evaluations fe ON fe.group_id = g.id
    WHERE p.department_id = $dept_id
    GROUP BY p.id
");
while ($row = mysqli_fetch_assoc($prog_res)) {
    $programs[] = [
        "name" => $row['name'],
        "code" => $row['code'],
        "avg" => round(($row['avg_marks'] ?? 0) * 5, 1)
    ];
}

// 6. Score Distribution
$dist = ["A" => 0, "B" => 0, "C" => 0, "D" => 0];
$dist_res = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN fe.marks_out_of_20 * 5 >= 80 THEN 1 ELSE 0 END) as A,
        SUM(CASE WHEN fe.marks_out_of_20 * 5 >= 70 AND fe.marks_out_of_20 * 5 < 80 THEN 1 ELSE 0 END) as B,
        SUM(CASE WHEN fe.marks_out_of_20 * 5 >= 50 AND fe.marks_out_of_20 * 5 < 70 THEN 1 ELSE 0 END) as C,
        SUM(CASE WHEN fe.marks_out_of_20 * 5 < 50 THEN 1 ELSE 0 END) as D
    FROM final_evaluations fe
    JOIN `groups` g ON fe.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    WHERE p.department_id = $dept_id
");
$dist_row = mysqli_fetch_assoc($dist_res);
$dist["A"] = (int)($dist_row['A'] ?? 0);
$dist["B"] = (int)($dist_row['B'] ?? 0);
$dist["C"] = (int)($dist_row['C'] ?? 0);
$dist["D"] = (int)($dist_row['D'] ?? 0);

echo json_encode([
    "success" => true,
    "stats" => [
        "total_students" => $total_students,
        "graded_count" => $graded_count,
        "avg_percentage" => $avg_percentage,
        "pass_percentage" => $pass_percentage
    ],
    "programs" => $programs,
    "distribution" => $dist
]);

mysqli_close($conn);
?>
