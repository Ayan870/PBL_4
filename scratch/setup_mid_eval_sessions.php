<?php
require_once __DIR__ . "/../config/db.php";
$res = mysqli_query($conn, "SHOW TABLES LIKE 'mid_eval_sessions'");
if (mysqli_num_rows($res) == 0) {
    $sql = "CREATE TABLE mid_eval_sessions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        department_id INT UNSIGNED NOT NULL,
        semester_id INT UNSIGNED NOT NULL,
        evaluator_id INT UNSIGNED NOT NULL,
        eval_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id),
        FOREIGN KEY (semester_id) REFERENCES semesters(id),
        FOREIGN KEY (evaluator_id) REFERENCES users(id)
    )";
    if (mysqli_query($conn, $sql)) {
        echo "Table mid_eval_sessions created successfully\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Table mid_eval_sessions already exists\n";
}
