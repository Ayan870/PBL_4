<?php
require_once __DIR__ . "/../config/db.php";
$sql = "CREATE TABLE IF NOT EXISTS evaluations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    supervisor_id INT UNSIGNED NOT NULL,
    eval_type VARCHAR(50) NOT NULL,
    tech_score INT DEFAULT 0,
    doc_score INT DEFAULT 0,
    pres_score INT DEFAULT 0,
    overall_rating INT DEFAULT 3,
    feedback TEXT,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (supervisor_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "TABLE CREATED\n";
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}
