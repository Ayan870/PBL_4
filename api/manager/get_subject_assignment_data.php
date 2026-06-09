<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

header('Content-Type: application/json');

$manager_id = $_SESSION['user_id'];

try {
    // Get manager's department
    $dept_query = "SELECT department_id FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $dept_query);
    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);
    $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $dept_id = $user_data['department_id'] ?? null;

    if (!$dept_id) {
        throw new Exception('Department not found for this manager.');
    }

    // Fetch all classes in this department
    $classes_query = "
        SELECT 
            c.id, 
            p.name as program_name, 
            s.number as semester_number, 
            c.section,
            ps.id as pbl_subject_id,
            ps.title as pbl_subject_title
        FROM classes c
        JOIN programs p ON c.program_id = p.id
        JOIN semesters s ON c.semester_id = s.id
        LEFT JOIN pbl_subjects ps ON (ps.program_id = c.program_id AND ps.semester_id = c.semester_id)
        WHERE p.department_id = ?
        ORDER BY p.name ASC, s.number ASC, c.section ASC
    ";
    $stmt = mysqli_prepare($conn, $classes_query);
    mysqli_stmt_bind_param($stmt, "i", $dept_id);
    mysqli_stmt_execute($stmt);
    $classes_res = mysqli_stmt_get_result($stmt);
    
    $classes = [];
    while ($row = mysqli_fetch_assoc($classes_res)) {
        // Group by class ID to avoid duplicates if multiple subjects are assigned (though there should be one)
        if (!isset($classes[$row['id']])) {
            $classes[$row['id']] = $row;
        }
    }

    // Fetch available subjects
    $subjects_query = "SELECT id, title FROM subjects_master ORDER BY title ASC";
    $subjects_res = mysqli_query($conn, $subjects_query);
    if (!$subjects_res) {
        throw new Exception('Subjects table not found. Please ensure database is updated.');
    }
    
    $available_subjects = [];
    while ($row = mysqli_fetch_assoc($subjects_res)) {
        $available_subjects[] = $row;
    }

    echo json_encode([
        'success' => true,
        'classes' => array_values($classes),
        'subjects' => $available_subjects
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
