<?php
require_once __DIR__ . "/../config/db.php";
$sql = "CREATE TABLE IF NOT EXISTS final_eval_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    evaluator_id INT UNSIGNED NOT NULL,
    status ENUM('running', 'closed') DEFAULT 'running',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (evaluator_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "SUCCESS\n";
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}
