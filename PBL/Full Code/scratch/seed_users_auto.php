<?php
require_once __DIR__ . '/../config/db.php';

$names = ["Ahmed", "Ali", "Hassan", "Hussain", "Omar", "Usman", "Zaid", "Bilal", "Tariq", "Saad", "Faisal", "Hamza", "Abdullah", "Ibrahim", "Ismail", "Yusuf", "Musa", "Isa", "Haroon", "Yahya", "Zainab", "Fatima", "Ayesha", "Khadija", "Maryam", "Hafsa", "Amina", "Ruqayyah", "Safiyya", "Maimoona"];

$password_hash = password_hash('123456', PASSWORD_DEFAULT);

// 1. Insert 10 supervisors in each department
$res_dept = mysqli_query($conn, "SELECT id, name FROM departments");
while ($dept = mysqli_fetch_assoc($res_dept)) {
    $dept_id = $dept['id'];
    for ($i = 1; $i <= 10; $i++) {
        $name = $names[array_rand($names)] . " " . $names[array_rand($names)];
        $email = "supervisor_" . $dept_id . "_" . time() . "_" . $i . mt_rand(1000, 9999) . "@university.edu";
        $role = 'supervisor';
        
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, role, department_id) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $password_hash, $role, $dept_id);
        mysqli_stmt_execute($stmt);
    }
    echo "Inserted 10 supervisors for department {$dept['name']}\n";
}

// 2. Insert 10 students in each class
$q_class = "
    SELECT c.id as class_id, c.program_id, c.semester_id, p.department_id, p.code as prog_code 
    FROM classes c
    JOIN programs p ON c.program_id = p.id
";
$res_class = mysqli_query($conn, $q_class);
while ($cls = mysqli_fetch_assoc($res_class)) {
    $prog_id = $cls['program_id'];
    $sem_id = $cls['semester_id'];
    $dept_id = $cls['department_id'];
    $prog_code = $cls['prog_code'];
    $class_id = $cls['class_id'];
    
    for ($i = 1; $i <= 10; $i++) {
        $name = $names[array_rand($names)] . " " . $names[array_rand($names)];
        $rand_roll = mt_rand(10, 99);
        $roll_number = strtoupper($prog_code) . "-" . $sem_id . $class_id . "-" . str_pad($i, 3, "0", STR_PAD_LEFT) . $rand_roll;
        $email = strtolower($roll_number) . "@university.edu";
        $role = 'student';
        
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, role, roll_number, program_id, department_id, semester_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssiii", $name, $email, $password_hash, $role, $roll_number, $prog_id, $dept_id, $sem_id);
        mysqli_stmt_execute($stmt);
    }
    echo "Inserted 10 students for class {$class_id} (Program {$prog_code}, Semester {$sem_id})\n";
}

echo "Seeding completed successfully!\n";
?>
