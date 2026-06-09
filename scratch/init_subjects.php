<?php
require_once "../config/db.php";

echo "Initializing Subjects Master Table...<br>";

$sql = "CREATE TABLE IF NOT EXISTS subjects_master (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(191) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subject_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "Table 'subjects_master' created or already exists.<br>";
} else {
    die("Error creating table: " . mysqli_error($conn));
}

$subjects = ['OOP', 'PF', 'DSA', 'Database', 'Web dev', 'Mobile App dev', 'Machine Learning', 'Deep Learning', 'Robotic AI', 'Intro to AI'];

foreach ($subjects as $s) {
    $ins = "INSERT IGNORE INTO subjects_master (title) VALUES ('$s')";
    mysqli_query($conn, $ins);
}

echo "Predefined subjects inserted.<br>";
echo "Done. You can now refresh the 'PBL Subjects' page.";
?>
