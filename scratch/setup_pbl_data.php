<?php
require_once __DIR__ . "/../config/db.php";

echo "Setting up PBL data (Classes, Subjects, Supervisors)...\n";

// 1. Get current semester
$sem_res = mysqli_query($conn, "SELECT id FROM semesters ORDER BY id DESC LIMIT 1");
$semester = mysqli_fetch_assoc($sem_res);
if (!$semester) {
    die("No semester found. Please check semesters table.\n");
}
$semester_id = $semester['id'];

// 2. Get a supervisor
$sup_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'supervisor' LIMIT 1");
$supervisor = mysqli_fetch_assoc($sup_res);
if (!$supervisor) {
    // Create a dummy supervisor if none exists
    $pass = hash('sha256', 'password123');
    mysqli_query($conn, "INSERT INTO users (name, email, password_hash, role) VALUES ('Mr. Hamid', 'hamid@university.edu', '$pass', 'supervisor')");
    $supervisor_id = mysqli_insert_id($conn);
    echo "Created dummy supervisor: Mr. Hamid (ID $supervisor_id)\n";
} else {
    $supervisor_id = $supervisor['id'];
    echo "Using existing supervisor ID $supervisor_id\n";
}

// 3. Setup classes and subjects for programs
$prog_res = mysqli_query($conn, "SELECT id, code FROM programs WHERE department_id = (SELECT id FROM departments WHERE code = 'CSIT' LIMIT 1)");
while ($prog = mysqli_fetch_assoc($prog_res)) {
    $prog_id = $prog['id'];
    $prog_code = $prog['code'];
    
    // Create class if not exists
    $check_class = mysqli_query($conn, "SELECT id FROM classes WHERE program_id = $prog_id AND semester_id = $semester_id LIMIT 1");
    if (mysqli_num_rows($check_class) == 0) {
        mysqli_query($conn, "INSERT INTO classes (program_id, semester_id, section) VALUES ($prog_id, $semester_id, 'A')");
        $class_id = mysqli_insert_id($conn);
        echo "Created class for $prog_code (ID $class_id)\n";
    } else {
        $class_id = mysqli_fetch_assoc($check_class)['id'];
        echo "Class for $prog_code already exists (ID $class_id)\n";
    }
    
    // Create subject if not exists
    $subject_title = "PBL Project - " . $prog_code;
    $check_sub = mysqli_query($conn, "SELECT id FROM pbl_subjects WHERE program_id = $prog_id AND semester_id = $semester_id LIMIT 1");
    if (mysqli_num_rows($check_sub) == 0) {
        mysqli_query($conn, "INSERT INTO pbl_subjects (program_id, semester_id, title) VALUES ($prog_id, $semester_id, '$subject_title')");
        $subject_id = mysqli_insert_id($conn);
        echo "Created subject '$subject_title' (ID $subject_id)\n";
    } else {
        $subject_id = mysqli_fetch_assoc($check_sub)['id'];
        echo "Subject '$subject_title' already exists (ID $subject_id)\n";
    }
    
    // Assign supervisor to class for this subject
    $check_sup = mysqli_query($conn, "SELECT id FROM class_supervisors WHERE class_id = $class_id AND pbl_subject_id = $subject_id LIMIT 1");
    if (mysqli_num_rows($check_sup) == 0) {
        mysqli_query($conn, "INSERT INTO class_supervisors (class_id, supervisor_id, pbl_subject_id) VALUES ($class_id, $supervisor_id, $subject_id)");
        echo "Assigned supervisor to class $class_id for subject $subject_id\n";
    }
}

echo "Setup complete.\n";
