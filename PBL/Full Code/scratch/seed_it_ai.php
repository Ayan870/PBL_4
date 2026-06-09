<?php
require_once __DIR__ . '/../config/db.php';

$names = ["Ahmed", "Ali", "Hassan", "Hussain", "Omar", "Usman", "Zaid", "Bilal", "Tariq", "Saad", "Faisal", "Hamza", "Abdullah", "Ibrahim", "Ismail", "Yusuf", "Musa", "Isa", "Haroon", "Yahya", "Zainab", "Fatima", "Ayesha", "Khadija", "Maryam", "Hafsa", "Amina", "Ruqayyah", "Safiyya", "Maimoona"];

$password_hash = password_hash('123456', PASSWORD_DEFAULT);

$programs = [
    2 => 'BSITM', // IT
    3 => 'BSAIM'  // AI
];
$dept_id = 1; // CS & IT

for ($sem_number = 1; $sem_number <= 6; $sem_number++) {
    // Get or create semester
    $q_sem = "SELECT id FROM semesters WHERE number = $sem_number LIMIT 1";
    $res_sem = mysqli_query($conn, $q_sem);
    if (mysqli_num_rows($res_sem) > 0) {
        $sem_id = mysqli_fetch_assoc($res_sem)['id'];
    } else {
        mysqli_query($conn, "INSERT INTO semesters (number, session, year) VALUES ($sem_number, 'Fall', 2024)");
        $sem_id = mysqli_insert_id($conn);
    }

    foreach ($programs as $prog_id => $prog_code) {
        // Get or create class
        $q_class = "SELECT id FROM classes WHERE program_id = $prog_id AND semester_id = $sem_id AND section = 'A'";
        $res_class = mysqli_query($conn, $q_class);
        if (mysqli_num_rows($res_class) > 0) {
            $class_id = mysqli_fetch_assoc($res_class)['id'];
        } else {
            mysqli_query($conn, "INSERT INTO classes (program_id, semester_id, section) VALUES ($prog_id, $sem_id, 'A')");
            $class_id = mysqli_insert_id($conn);
        }
        
        // Count existing students
        $q_count = "SELECT count(*) as cnt FROM users WHERE role = 'student' AND program_id = $prog_id AND semester_id = $sem_id";
        $res_count = mysqli_query($conn, $q_count);
        $current_count = mysqli_fetch_assoc($res_count)['cnt'];
        
        $needed = 10 - $current_count;
        if ($needed > 0) {
            for ($i = 1; $i <= $needed; $i++) {
                $name = $names[array_rand($names)] . " " . $names[array_rand($names)];
                $rand_roll = mt_rand(100, 999);
                $roll_number = strtoupper($prog_code) . "-S" . $sem_number . "C" . $class_id . "-" . str_pad($i, 3, "0", STR_PAD_LEFT) . $rand_roll;
                $email = strtolower($roll_number) . "@university.edu";
                $role = 'student';
                
                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, role, roll_number, program_id, department_id, semester_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssssiii", $name, $email, $password_hash, $role, $roll_number, $prog_id, $dept_id, $sem_id);
                mysqli_stmt_execute($stmt);
            }
            echo "Inserted $needed students for $prog_code Semester $sem_number (Class ID: $class_id)\n";
        } else {
            echo "Class $prog_code Semester $sem_number already has $current_count students.\n";
        }
    }
}

echo "Seeding IT and AI students completed successfully!\n";
?>
