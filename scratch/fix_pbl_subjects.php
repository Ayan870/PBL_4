<?php
require_once __DIR__ . '/../config/db.php';

// First, ensure subjects_master is initialized
$subjects = ['OOP', 'PF', 'DSA', 'Database', 'Web dev', 'Mobile App dev', 'Machine Learning', 'Deep Learning', 'Robotic AI', 'Intro to AI'];
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subjects_master (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(191) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subject_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

foreach ($subjects as $s) {
    mysqli_query($conn, "INSERT IGNORE INTO subjects_master (title) VALUES ('$s')");
}

// Semester mapping: number => title(s) for IT and AI
$mapping = [
    1 => ['shared' => ['PF']],
    2 => ['shared' => ['OOP']],
    3 => ['shared' => ['DSA']],
    4 => ['shared' => ['Database']],
    5 => ['IT' => ['Web dev'], 'AI' => ['Machine Learning', 'Intro to AI']],
    6 => ['IT' => ['Mobile App dev'], 'AI' => ['Deep Learning', 'Robotic AI']]
];

$programs = [
    2 => 'IT',
    3 => 'AI'
];

// Clean up existing PBL subjects for these programs
$res = mysqli_query($conn, "SELECT id FROM pbl_subjects WHERE program_id IN (2, 3)");
while ($row = mysqli_fetch_assoc($res)) {
    $sid = $row['id'];
    mysqli_query($conn, "DELETE FROM class_supervisors WHERE pbl_subject_id = $sid");
    mysqli_query($conn, "DELETE FROM pbl_subjects WHERE id = $sid");
}

for ($sem_number = 1; $sem_number <= 6; $sem_number++) {
    // Get semester ID
    $q_sem = "SELECT id FROM semesters WHERE number = $sem_number AND year = 2023 LIMIT 1"; // This corresponds to the ones we used for students
    $res_sem = mysqli_query($conn, $q_sem);
    if (mysqli_num_rows($res_sem) > 0) {
        $sem_id = mysqli_fetch_assoc($res_sem)['id'];
    } else {
        continue;
    }

    foreach ($programs as $prog_id => $prog_type) {
        $titles_to_insert = [];
        
        if (isset($mapping[$sem_number]['shared'])) {
            $titles_to_insert = array_merge($titles_to_insert, $mapping[$sem_number]['shared']);
        }
        if (isset($mapping[$sem_number][$prog_type])) {
            $titles_to_insert = array_merge($titles_to_insert, $mapping[$sem_number][$prog_type]);
        }
        
        foreach ($titles_to_insert as $title) {
            $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO pbl_subjects (semester_id, program_id, title) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iis", $sem_id, $prog_id, $title);
            mysqli_stmt_execute($stmt);
            echo "Inserted '$title' for $prog_type in Semester $sem_number (ID $sem_id)\n";
        }
    }
}

echo "PBL subjects updated successfully!\n";
?>
